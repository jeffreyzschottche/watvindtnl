<?php

namespace App\Services\Reports;

use App\Models\Issue;
use App\Support\CompassEditorialCatalogue;
use Illuminate\Support\Facades\DB;

class CompassEditorialSeed
{
    public function run(?int $actor = null): array
    {
        $result = $this->catalogue($actor);
        // Weights approved elsewhere (exported to resources/content) arrive with the same seed click.
        $result['weights_file'] = app(CompassWeightsFile::class)->seedFromFile($actor);

        return $result;
    }

    private function catalogue(?int $actor): array
    {
        return DB::transaction(function () use ($actor) {
            $result = ['reviewed' => 0, 'unchanged' => 0, 'skipped_existing' => 0, 'skipped_changed' => 0, 'missing' => 0, 'catalogue' => CompassEditorialCatalogue::VERSION];
            $hashes = json_decode(file_get_contents(resource_path('content/compass-review-hashes.json')), true, flags: JSON_THROW_ON_ERROR);
            foreach (CompassEditorialCatalogue::rows() as $row) {
                foreach ($row['slugs'] as $slug) {
                    $issue = Issue::where('slug', $slug)->lockForUpdate()->first();
                    if (! $issue) {
                        $result['missing']++;

                        continue;
                    }
                    $textHash = hash('sha256', json_encode([$issue->title, $issue->description, $issue->more_info], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    if (! hash_equals($hashes[$slug] ?? '', $textHash)) {
                        $result['skipped_changed']++;

                        continue;
                    }
                    $before = DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->lockForUpdate()->first();
                    $prior = $before?->compass_context ? json_decode($before->compass_context, true) : [];
                    if (($prior['catalogue'] ?? null) === CompassEditorialCatalogue::VERSION) {
                        $result['unchanged']++;

                        continue;
                    }
                    if ($before && ($before->compass_reviewed_at || ($before->compass_weights && json_decode($before->compass_weights, true)))) {
                        $result['skipped_existing']++;

                        continue;
                    }
                    // Review means interpretation of the existing wording; verified_at and official party evidence stay untouched.
                    $context = ['canonical_key' => $row['key'], 'rationale' => $row['rationale'], 'topics' => $row['topics'], 'historical' => true, 'catalogue' => CompassEditorialCatalogue::VERSION, 'basis' => 'statement_wording', 'text_hash' => $textHash];
                    $data = ['compass_weights' => json_encode($row['weights'], JSON_THROW_ON_ERROR), 'compass_context' => json_encode($context, JSON_THROW_ON_ERROR), 'compass_reviewed_at' => now(), 'revision' => ($before->revision ?? 0) + 1, 'updated_at' => now()];
                    if ($before) {
                        DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->update($data);
                    } else {
                        DB::table('wdnl_statement_metadata')->insert($data + ['issue_id' => $issue->id, 'created_at' => now()]);
                    }
                    DB::table('wdnl_content_audits')->insert(['action' => 'compass.review', 'token_id' => $actor, 'resource_key' => $slug, 'before' => $before ? json_encode((array) $before, JSON_THROW_ON_ERROR) : null, 'after' => json_encode(['context' => $context, 'weights' => $row['weights']], JSON_THROW_ON_ERROR), 'created_at' => now()]);
                    $result['reviewed']++;
                }
            }

            $result['official_references'] = app(OfficialCompassReferences::class)->seed($actor);

            return $result;
        }, 3);
    }
}
