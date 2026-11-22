<?php

namespace App\Services;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Older statements were imported without a link to their motion. This finds the official
 * motion again, has the AI confirm it is the same proposal and points the same way, and only
 * then attaches the official party votes so the report can compare parties.
 */
class LegacyMotionLinker
{
    private const STOPWORDS = ['voor', 'naar', 'over', 'van', 'met', 'door', 'zonder', 'tegen', 'bij', 'een', 'het', 'de', 'en', 'of', 'in', 'op', 'aan',
        'om', 'te', 'uit', 'tot', 'als', 'meer', 'minder', 'extra', 'betere', 'beter', 'nieuwe', 'nieuw', 'alle', 'geen', 'niet', 'wel', 'ook', 'onder',
        'binnen', 'tussen', 'vanuit', 'rond', 'rondom', 'regering', 'kabinet', 'verzoek', 'motie', 'onderzoek', 'nederland', 'nederlandse', 'invoeren',
        'versterken', 'verbeteren', 'stoppen', 'starten', 'zorgen', 'waarbij', 'mogelijk', 'mogelijkheid', 'aanpak', 'plan', 'maatregelen'];

    private const MAX_CANDIDATES = 20;

    public function __construct(private MotionVotes $votes, private MotionStatementCheck $check, private CmsContentService $content) {}

    /** @return array{status:string, number?:string, subject?:string, direction?:string, votes?:bool, message?:string} */
    public function link(Issue $issue, bool $dryRun = false): array
    {
        try {
            $candidates = $this->candidates($issue);
            if (! $candidates) {
                return $this->record($issue, ['status' => 'no_candidates'], $dryRun);
            }
            $verdict = $this->check->judge(
                ['title' => $issue->title, 'description' => $issue->description, 'more_info' => $issue->more_info],
                array_map(fn ($c) => ['number' => $c['Nummer'], 'subject' => trim($c['Onderwerp']), 'date' => substr($c['GestartOp'] ?? '', 0, 10) ?: null], $candidates),
            );
            $picked = $verdict['match'] !== null ? $candidates[$verdict['match']] : null;
            $base = ['number' => $picked['Nummer'] ?? null, 'subject' => isset($picked) ? trim($picked['Onderwerp']) : null,
                'direction' => $verdict['direction'], 'confidence' => $verdict['confidence'], 'model' => $verdict['model']];
            if (! MotionStatementCheck::accepted($verdict)) {
                return $this->record($issue, ['status' => $picked ? 'not_confirmed' : 'no_match'] + $base, $dryRun);
            }
            if (DB::table('wdnl_motions as mo')->join('wdnl_statement_metadata as m', 'm.motion_id', '=', 'mo.id')
                ->where('mo.external_id', $picked['Id'])->where('m.issue_id', '!=', $issue->id)->exists()) {
                return $this->record($issue, ['status' => 'motion_already_linked'] + $base, $dryRun);
            }
            $stances = $this->votes->stances($this->votes->zaak($picked['Id']));
            $result = ['status' => 'linked', 'votes' => MotionVotes::hasVotes($stances)] + $base;
            if (! $dryRun) {
                $this->attach($issue, $picked, $stances, $result);
            }

            return $result;
        } catch (Throwable $e) {
            report($e);

            // Errors are not recorded, so a next run retries this statement.
            return ['status' => 'error', 'message' => mb_substr($e->getMessage(), 0, 200)];
        }
    }

    /** Official motions found via AI search terms and via the title's own words, merged. */
    public function candidates(Issue $issue): array
    {
        $terms = $this->check->searchTerms(['title' => $issue->title, 'description' => $issue->description, 'more_info' => $issue->more_info]);
        $words = $this->keywords($issue->title);
        $found = [];
        foreach ([[$terms, 10], [$words, 6]] as [$list, $enough]) {
            $before = count($found);
            foreach ($this->queries($list) as $query) {
                foreach ($this->search($query) as $row) {
                    $found[$row['Id']] ??= $row;
                }
                if (count($found) - $before >= $enough) {
                    break;
                }
            }
        }

        return array_slice(array_values($found), 0, self::MAX_CANDIDATES);
    }

    /** Pairs of the first three terms (precise), then the terms on their own (broad). */
    private function queries(array $terms): array
    {
        $terms = array_values(array_slice(array_unique($terms), 0, 3));
        $queries = [];
        for ($i = 0; $i < count($terms); $i++) {
            for ($j = $i + 1; $j < count($terms); $j++) {
                $queries[] = [$terms[$i], $terms[$j]];
            }
        }
        foreach ($terms as $term) {
            $queries[] = [$term];
        }

        return $queries;
    }

    /** @return list<string> distinctive words, longest first */
    public function keywords(string $title): array
    {
        $words = preg_split('/[^\p{L}\p{N}-]+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, self::STOPWORDS, true) && ! ctype_digit($w));
        $words = array_values(array_unique($words));
        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return array_slice($words, 0, 4);
    }

    private function search(array $words): array
    {
        $filter = "Soort eq 'Motie' and Verwijderd eq false";
        foreach ($words as $word) {
            $filter .= " and contains(Onderwerp,'".str_replace("'", "''", $word)."')";
        }
        $response = Http::acceptJson()->timeout(12)->connectTimeout(4)->retry(2, 500, throw: false)
            ->withOptions(['allow_redirects' => false])
            ->get(CmsMotionSource::BASE.'/Zaak', ['$filter' => $filter, '$select' => 'Id,Nummer,Onderwerp,GestartOp', '$orderby' => 'GestartOp desc', '$top' => 8]);
        if (! $response->successful() || ! is_array($response->json('value'))) {
            throw new \RuntimeException('De Tweede Kamer-API gaf geen zoekresultaten.');
        }
        usleep((int) config('motion-import.request_delay_microseconds', 150000));

        return array_values(array_filter($response->json('value'), fn ($r) => is_array($r) && isset($r['Id'], $r['Nummer'], $r['Onderwerp'])));
    }

    private function attach(Issue $issue, array $zaak, array $stances, array $result): void
    {
        DB::transaction(function () use ($issue, $zaak, $stances, $result) {
            $motion = $this->content->saveMotion([
                'external_id' => $zaak['Id'], 'number' => $zaak['Nummer'], 'title' => mb_substr(trim($zaak['Onderwerp']), 0, 4000),
                'source_url' => CmsMotionSource::BASE.'/Zaak('.$zaak['Id'].')', 'source_date' => substr($zaak['GestartOp'], 0, 10),
            ], null, sourceSync: true);
            $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->lockForUpdate()->first();
            $before = ['motion_id' => $meta?->motion_id, 'party_stances' => $issue->party_stances];
            $issue->update(['party_stances' => $stances]);
            $values = [
                'motion_id' => $motion['id'], 'source_date' => substr($zaak['GestartOp'], 0, 10),
                'origin_kind' => $meta?->origin_kind ?? 'motion', 'source_name' => $meta?->source_name ?? 'Tweede Kamer',
                'source_title' => $meta?->source_title ?? mb_substr(trim($zaak['Onderwerp']), 0, 500),
                // Verified = the official motion and the direction were confirmed (here automatically, see motion_match).
                'verified_at' => now(), 'motion_match' => json_encode(['method' => 'auto'] + $result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'motion_match_checked_at' => now(), 'updated_at' => now(),
            ];
            if ($meta) {
                DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->update($values + ['revision' => $meta->revision + 1]);
            } else {
                DB::table('wdnl_statement_metadata')->insert($values + ['issue_id' => $issue->id, 'revision' => 1, 'created_at' => now()]);
            }
            $this->content->audit('statement.motion_link', $issue->slug, $before, ['motion_id' => $motion['id'], 'party_stances' => $stances, 'match' => $result], null);
        });
    }

    private function record(Issue $issue, array $result, bool $dryRun): array
    {
        if (! $dryRun) {
            $values = ['motion_match' => json_encode(['method' => 'auto'] + $result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'motion_match_checked_at' => now(), 'updated_at' => now()];
            if (DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->exists()) {
                DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->update($values);
            } else {
                DB::table('wdnl_statement_metadata')->insert($values + ['issue_id' => $issue->id, 'revision' => 0, 'created_at' => now()]);
            }
        }

        return $result;
    }
}
