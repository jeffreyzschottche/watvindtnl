<?php

namespace App\Services\Reports;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Reviewed compass weights as portable content: exported from one environment, seeded into another.
 * Rows are matched on slug and exact wording; changed text or an existing review is never overwritten.
 */
class CompassWeightsFile
{
    public const VERSION = 'compass-weights-v1';

    public static function path(): string
    {
        return resource_path('content/compass-weights.json');
    }

    public function export(): array
    {
        $rows = [];
        $records = DB::table('wdnl_statement_metadata as m')->join('issues as i', 'i.id', '=', 'm.issue_id')->whereNotNull('m.compass_reviewed_at')
            ->orderBy('i.slug')->get(['i.slug', 'i.title', 'i.description', 'i.more_info', 'm.compass_weights', 'm.compass_context', 'm.compass_reviewed_at']);
        foreach ($records as $r) {
            $hash = CompassProposals::textHash($r);
            if (! CompassProposals::reviewedForCurrentText($r, $hash)) {
                continue;
            }
            $context = json_decode((string) $r->compass_context, true) ?: [];
            $rows[] = ['slug' => $r->slug, 'text_hash' => $hash, 'weights' => (object) array_filter((array) json_decode((string) $r->compass_weights, true)),
                'context' => array_intersect_key($context, array_flip(['canonical_key', 'rationale', 'topics', 'historical', 'catalogue', 'basis']))];
        }

        return ['version' => self::VERSION, 'rows' => $rows];
    }

    public function write(): int
    {
        $data = $this->export();
        file_put_contents(self::path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        return count($data['rows']);
    }

    public function seedFromFile(?int $actor = null): array
    {
        if (! is_file(self::path())) {
            return ['applied' => 0, 'already_reviewed' => 0, 'changed_text' => 0, 'missing' => 0];
        }

        return $this->apply(json_decode(file_get_contents(self::path()), true, flags: JSON_THROW_ON_ERROR), $actor);
    }

    public function apply(array $payload, ?int $actor = null): array
    {
        $data = Validator::make($payload, [
            'version' => ['required', 'in:'.self::VERSION], 'rows' => ['present', 'array', 'max:20000'],
            'rows.*.slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'rows.*.text_hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'rows.*.weights' => ['present', 'array:'.implode(',', CompassProposals::DIMENSIONS)], 'rows.*.weights.*' => ['integer', 'between:-2,2'],
            'rows.*.context' => ['required', 'array:canonical_key,rationale,topics,historical,catalogue,basis'],
            'rows.*.context.rationale' => ['required', 'string', 'max:2000'], 'rows.*.context.topics' => ['sometimes', 'array', 'max:4'], 'rows.*.context.topics.*' => ['string', 'max:80'],
            'rows.*.context.canonical_key' => ['nullable', 'string', 'max:180', 'regex:/^[a-zA-Z0-9:_-]+$/'], 'rows.*.context.historical' => ['sometimes', 'boolean'],
            'rows.*.context.catalogue' => ['nullable', 'string', 'max:100'], 'rows.*.context.basis' => ['nullable', 'string', 'max:100'],
        ])->validate();
        $result = ['applied' => 0, 'already_reviewed' => 0, 'changed_text' => 0, 'missing' => 0];
        foreach (array_chunk($data['rows'], 200) as $chunk) {
            DB::transaction(function () use ($chunk, $actor, &$result) {
                $issues = Issue::whereIn('slug', array_column($chunk, 'slug'))->lockForUpdate()->get(['id', 'slug', 'title', 'description', 'more_info'])->keyBy('slug');
                $metas = DB::table('wdnl_statement_metadata')->whereIn('issue_id', $issues->pluck('id'))->lockForUpdate()->get()->keyBy('issue_id');
                foreach ($chunk as $row) {
                    $issue = $issues->get($row['slug']);
                    if (! $issue) {
                        $result['missing']++;

                        continue;
                    }
                    $hash = CompassProposals::textHash($issue);
                    $meta = $metas->get($issue->id);
                    if (! hash_equals($row['text_hash'], $hash)) {
                        $result['changed_text']++;

                        continue;
                    }
                    if (CompassProposals::reviewedForCurrentText($meta, $hash)) {
                        $result['already_reviewed']++;

                        continue;
                    }
                    $weights = array_filter(array_map('intval', $row['weights']));
                    $context = $row['context'] + ['text_hash' => $hash];
                    $values = ['compass_weights' => json_encode((object) $weights, JSON_THROW_ON_ERROR), 'compass_context' => json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'compass_reviewed_at' => now(), 'revision' => ($meta->revision ?? 0) + 1, 'updated_at' => now()];
                    if ($meta) {
                        DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->update($values);
                    } else {
                        DB::table('wdnl_statement_metadata')->insert($values + ['issue_id' => $issue->id, 'created_at' => now()]);
                    }
                    DB::table('wdnl_content_audits')->insert(['action' => 'compass.weights.import', 'token_id' => $actor, 'resource_key' => $issue->slug, 'before' => $meta ? json_encode(['weights' => $meta->compass_weights]) : null,
                        'after' => json_encode(['weights' => $weights, 'context' => $context], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => now()]);
                    $result['applied']++;
                }
            }, 3);
        }

        return $result;
    }
}
