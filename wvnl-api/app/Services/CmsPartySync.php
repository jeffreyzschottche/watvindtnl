<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\PoliticalParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Brings the party list in line with the current Tweede Kamer factions. Votes from a
 * faction without a matching party are otherwise silently dropped from every comparison.
 * Former parties stay: their historic votes still count.
 */
class CmsPartySync
{
    /** Re-reading votes is one API call per statement; the button can simply be pressed again. */
    private const REVOTE_LIMIT = 60;

    public function __construct(private CmsContentService $content, private MotionVotes $votes) {}

    public function sync(?int $actor): array
    {
        $factions = $this->factions();
        $created = [];
        $logos = 0;
        $since = null;
        foreach ($factions as $f) {
            $party = $this->find($f['abbreviation'], $f['name']);
            if (! $party) {
                $party = PoliticalParty::create([
                    'name' => $f['name'], 'abbreviation' => $f['abbreviation'], 'slug' => $this->slug($f['abbreviation']),
                    'logo_url' => $f['logo_url'],
                ]);
                $this->content->audit('party.sync', $party->slug, null, $party->only(['name', 'abbreviation', 'logo_url']), $actor);
                $created[] = $party->abbreviation;
                $since = min($since ?? $f['active_since'], $f['active_since']);
            } elseif ($f['logo_url'] && (! $party->logo_url || str_contains($party->logo_url, '://localhost') || str_contains($party->logo_url, '://127.0.0.1'))) {
                $before = $party->only(['logo_url']);
                $party->update(['logo_url' => $f['logo_url']]);
                $this->content->audit('party.sync', $party->slug, $before, $party->only(['logo_url']), $actor);
                $logos++;
            }
        }

        // Statements on motions since the new factions appeared were linked without their votes.
        $revote = $since ? $this->revote($since, $actor) : ['checked' => 0, 'updated' => 0, 'remaining' => 0];
        $drafts = $since ? $this->redraft($since) : 0;

        return ['factions' => count($factions), 'created' => $created, 'logos' => $logos,
            'statements_checked' => $revote['checked'], 'statements_updated' => $revote['updated'], 'statements_remaining' => $revote['remaining'], 'drafts_updated' => $drafts];
    }

    private function factions(): array
    {
        try {
            $response = Http::acceptJson()->timeout(12)->connectTimeout(4)->withOptions(['allow_redirects' => false])
                ->get(CmsMotionSource::BASE.'/Fractie', [
                    '$filter' => 'Verwijderd eq false and DatumInactief eq null',
                    '$select' => 'Id,Afkorting,NaamNL,DatumActief,ContentType',
                ]);
        } catch (Throwable) {
            $response = null;
        }
        $rows = $response?->successful() ? $response->json('value') : null;
        if (! is_array($rows) || $rows === []) {
            throw ValidationException::withMessages(['bron' => 'De fractielijst van de Tweede Kamer kon niet worden gelezen. Probeer het later opnieuw.']);
        }
        $factions = [];
        foreach ($rows as $row) {
            $abbr = trim((string) ($row['Afkorting'] ?? ''));
            if ($abbr === '' || ! Str::isUuid($row['Id'] ?? '')) {
                continue;
            }
            $factions[] = [
                'abbreviation' => mb_substr($abbr, 0, 50), 'name' => mb_substr(trim((string) ($row['NaamNL'] ?? $abbr)) ?: $abbr, 0, 255),
                'active_since' => substr((string) ($row['DatumActief'] ?? ''), 0, 10) ?: now()->toDateString(),
                'logo_url' => ! empty($row['ContentType']) ? CmsMotionSource::BASE.'/Fractie('.$row['Id'].')/resource' : null,
            ];
        }

        return $factions;
    }

    private function find(string $abbreviation, string $name): ?PoliticalParty
    {
        $keys = array_unique(array_map(fn ($v) => mb_strtolower($v), [$abbreviation, $name, preg_replace('/^(lid|groep)\s+/i', '', $abbreviation)]));
        foreach (PoliticalParty::all() as $party) {
            if (in_array(mb_strtolower((string) $party->abbreviation), $keys, true) || in_array(mb_strtolower((string) $party->name), $keys, true)) {
                return $party;
            }
        }

        return null;
    }

    private function slug(string $abbreviation): string
    {
        $base = Str::slug($abbreviation) ?: 'fractie';
        $slug = $base;
        for ($i = 2; PoliticalParty::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function revote(string $since, ?int $actor): array
    {
        $rows = DB::table('wdnl_statement_metadata as m')->join('wdnl_motions as mo', 'mo.id', '=', 'm.motion_id')
            ->whereNotNull('m.verified_at')->where('mo.source_date', '>=', $since)
            ->orderByDesc('mo.source_date')->get(['m.issue_id', 'mo.external_id']);
        $checked = 0;
        $updated = 0;
        foreach ($rows->take(self::REVOTE_LIMIT) as $row) {
            $checked++;
            try {
                $stances = $this->votes->stances($this->votes->zaak($row->external_id));
            } catch (Throwable) {
                continue;
            }
            $issue = Issue::find($row->issue_id);
            if (! $issue || ! MotionVotes::hasVotes($stances) || $issue->party_stances == $stances) {
                continue;
            }
            $before = ['party_stances' => $issue->party_stances];
            $issue->update(['party_stances' => $stances]);
            $this->content->audit('statement.motion_votes', $issue->slug, $before, ['party_stances' => $stances], $actor);
            $updated++;
        }

        return ['checked' => $checked, 'updated' => $updated, 'remaining' => max(0, $rows->count() - self::REVOTE_LIMIT)];
    }

    /** Open AI drafts carry the votes from when they were written. */
    private function redraft(string $since): int
    {
        $updated = 0;
        $drafts = DB::table('wdnl_motion_statement_drafts as d')->join('wdnl_motions as mo', 'mo.id', '=', 'd.motion_id')
            ->where('mo.source_date', '>=', $since)->get(['d.motion_id', 'd.payload', 'mo.external_id']);
        foreach ($drafts as $draft) {
            try {
                $stances = $this->votes->stances($this->votes->zaak($draft->external_id));
            } catch (Throwable) {
                continue;
            }
            $payload = json_decode($draft->payload, true);
            if (! is_array($payload) || ! MotionVotes::hasVotes($stances) || ($payload['party_stances'] ?? null) == $stances) {
                continue;
            }
            $payload['party_stances'] = $stances;
            DB::table('wdnl_motion_statement_drafts')->where('motion_id', $draft->motion_id)->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            $updated++;
        }

        return $updated;
    }
}
