<?php

namespace App\Services\Reports;

use App\Models\Issue;
use App\Models\PoliticalParty;
use Illuminate\Support\Facades\DB;

/** Checked public parliamentary records, applied only to unchanged statements with exact case numbers. */
class OfficialCompassReferences
{
    public function seed(?int $actor = null): array
    {
        $file = json_decode(file_get_contents(resource_path('content/compass-official-references.json')), true, flags: JSON_THROW_ON_ERROR);

        return DB::transaction(function () use ($file, $actor) {
            $linked = 0;
            $skipped = 0;
            $parties = PoliticalParty::all()->keyBy(fn ($p) => mb_strtolower($p->abbreviation ?? ''));
            $issues = Issue::where(function ($q) use ($file) {
                foreach ($file['motions'] as $m) {
                    $q->orWhere('slug', 'like', '%'.strtolower($m['number']).'%');
                }
            })->get(['id', 'slug', 'title', 'description', 'more_info', 'party_stances']);
            foreach ($file['motions'] as $m) {
                foreach ($issues as $i) {
                    if (! str_contains(strtolower($i->slug), strtolower($m['number']))) {
                        continue;
                    }
                    $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $i->id)->first();
                    $ctx = $meta?->compass_context ? json_decode($meta->compass_context, true) : [];
                    $hash = hash('sha256', json_encode([$i->title, $i->description, $i->more_info], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    if (empty($ctx['text_hash']) || ! hash_equals($ctx['text_hash'], $hash)) {
                        $skipped++;

                        continue;
                    }
                    $stances = ['agree' => [], 'disagree' => [], 'neutral' => []];
                    foreach ($m['stances'] as $v) {
                        // Historical PvdA/GL are not silently combined into today's joint party.
                        $alias = mb_strtolower($v['ActorFractie']);
                        $party = $parties->get($alias === 'christenunie' ? 'cu' : $alias);
                        if (! $party) {
                            continue;
                        }
                        $side = match ($v['Soort']) {
                            'Voor' => 'agree','Tegen' => 'disagree',default => 'neutral'
                        };
                        $stances[$side][] = $party->id;
                    }
                    foreach ($stances as &$ids) {
                        $ids = array_values(array_unique($ids));
                        sort($ids);
                    }unset($ids);
                    $motion = DB::table('wdnl_motions')->where('external_id', $m['external_id'])->first();
                    if ($meta->motion_id && (! $motion || (int) $meta->motion_id !== (int) $motion->id)) {
                        $skipped++;

                        continue;
                    }
                    if ($motion && (int) $meta->motion_id === (int) $motion->id && $meta->verified_at && $i->party_stances == $stances) {
                        continue;
                    }
                    $motionId = $motion?->id ?? DB::table('wdnl_motions')->insertGetId(['external_id' => $m['external_id'], 'number' => $m['number'], 'title' => $m['title'], 'source_url' => $m['source_url'], 'source_date' => $m['source_date'], 'context' => json_encode(['decision_id' => $m['decision_id'], 'decision' => $m['decision'], 'odata_url' => $m['odata_url'], 'checked_on' => $file['checked_on'], 'stances' => $m['stances']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
                    $before = ['motion_id' => $meta->motion_id, 'party_stances' => $i->party_stances];
                    $i->update(['party_stances' => $stances]);
                    DB::table('wdnl_statement_metadata')->where('issue_id', $i->id)->update(['motion_id' => $motionId, 'source_date' => $m['source_date'], 'verified_at' => now(), 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
                    DB::table('wdnl_content_audits')->insert(['action' => 'compass.references.seed', 'token_id' => $actor, 'resource_key' => 'issue:'.$i->id, 'before' => json_encode($before), 'after' => json_encode(['motion_id' => $motionId, 'party_stances' => $stances, 'source' => $m['source_url']]), 'created_at' => now()]);
                    $linked++;
                }
            }

            return ['linked' => $linked, 'modified_text_skipped' => $skipped, 'checked_on' => $file['checked_on']];
        });
    }
}
