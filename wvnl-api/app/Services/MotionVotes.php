<?php

namespace App\Services;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Official party votes per motion, straight from the Tweede Kamer open data. */
class MotionVotes
{
    public function __construct(private MotionImportService $import, private CmsContentService $content) {}

    /** The motion with its decisions and party votes, without the (slow) PDF text. */
    public function zaak(string $externalId): array
    {
        if (! Str::isUuid($externalId)) {
            throw new RuntimeException('Geen officieel Tweede Kamer-ID.');
        }
        $response = Http::acceptJson()->timeout(10)->connectTimeout(4)->retry(2, 500, throw: false)
            ->withOptions(['allow_redirects' => false])
            ->get(CmsMotionSource::BASE.'/Zaak('.$externalId.')', [
                '$select' => 'Id,Soort,Verwijderd,Nummer,Onderwerp,GestartOp',
                '$expand' => 'Besluit($filter=Verwijderd eq false;$select=BesluitTekst;$expand=Stemming($filter=Verwijderd eq false;$select=Soort,ActorNaam,ActorFractie))',
            ]);
        $zaak = $response->json();
        if (! $response->successful() || ($zaak['Id'] ?? null) !== $externalId || ($zaak['Soort'] ?? null) !== 'Motie') {
            throw new RuntimeException('De officiële motie is niet beschikbaar.');
        }

        return $zaak;
    }

    public function stances(array $zaak): array
    {
        return $this->import->officialPartyStances($zaak);
    }

    public static function hasVotes(mixed $stances): bool
    {
        $stances = (array) $stances;

        return ! empty($stances['agree']) || ! empty($stances['disagree']);
    }

    /**
     * Motions are often voted on weeks after a statement was written. Each sync fills in the
     * official votes for linked statements that do not have them yet.
     */
    public function refreshLinked(int $limit = 25, ?int $actor = null): array
    {
        $rows = DB::table('wdnl_statement_metadata as m')
            ->join('wdnl_motions as mo', 'mo.id', '=', 'm.motion_id')
            ->join('issues as i', 'i.id', '=', 'm.issue_id')
            ->whereNotNull('m.verified_at')
            ->orderByDesc('mo.source_date')
            ->get(['m.issue_id', 'mo.external_id', 'i.party_stances'])
            ->reject(fn ($row) => self::hasVotes(json_decode((string) $row->party_stances, true)))
            ->take($limit);
        $updated = 0;
        $failed = 0;
        foreach ($rows as $row) {
            try {
                $stances = $this->stances($this->zaak($row->external_id));
            } catch (Throwable) {
                $failed++;

                continue;
            }
            if (! self::hasVotes($stances)) {
                continue; // not voted on (yet), or decided without a recorded vote
            }
            $issue = Issue::find($row->issue_id);
            $before = ['party_stances' => $issue->party_stances];
            $issue->update(['party_stances' => $stances]);
            $this->content->audit('statement.motion_votes', $issue->slug, $before, ['party_stances' => $stances], $actor);
            $updated++;
        }

        return ['checked' => $rows->count(), 'updated' => $updated, 'failed' => $failed];
    }
}
