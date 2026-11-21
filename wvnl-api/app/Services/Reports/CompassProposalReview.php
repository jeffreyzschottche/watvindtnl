<?php

namespace App\Services\Reports;

use App\Models\Issue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Editor decisions on model-suggested weights. Approval writes the same reviewed metadata as a manual edit. */
class CompassProposalReview
{
    public function list(array $filters): array
    {
        $data = Validator::make($filters, [
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'outdated'])],
            'confidence' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ])->validate();
        $status = $data['status'] ?? 'pending';
        $page = DB::table('wdnl_compass_proposals as p')->join('issues as i', 'i.id', '=', 'p.issue_id')
            ->where('p.status', $status)
            ->when($data['confidence'] ?? null, fn ($q, $c) => $q->where('p.confidence', $c))
            ->orderByRaw("CASE p.confidence WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")->orderByDesc('p.id')
            ->select(['p.id', 'p.issue_id', 'p.weights', 'p.topics', 'p.rationale', 'p.confidence', 'p.status', 'p.model', 'p.created_at', 'i.title', 'i.slug', 'i.description'])
            ->paginate(25, ['*'], 'page', $data['page'] ?? 1);
        $page->getCollection()->transform(fn ($r) => array_merge((array) $r, [
            'weights' => json_decode($r->weights, true) ?: (object) [], 'topics' => json_decode($r->topics, true), 'description' => mb_substr((string) $r->description, 0, 400),
        ]));
        $counts = DB::table('wdnl_compass_proposals')->select('status', 'confidence', DB::raw('count(*) as total'))->groupBy('status', 'confidence')->get();
        $runs = DB::table('wdnl_compass_proposal_runs');

        return ['proposals' => $page, 'counts' => $counts, 'without_weights' => Issue::whereNotExists(fn ($q) => $q->from('wdnl_statement_metadata as m')->whereColumn('m.issue_id', 'issues.id')->whereNotNull('m.compass_reviewed_at'))->count(),
            'spent_eur' => ((int) (clone $runs)->sum('cost_eur_micros')) / 1000000, 'dimensions' => CompassAnalysis::DIMENSIONS, 'topics' => CompassProposals::TOPICS];
    }

    public function approve(int $id, ?array $weights, ?int $actor): array
    {
        if ($weights !== null) {
            $weights = Validator::make(['weights' => $weights], ['weights' => ['array:'.implode(',', CompassProposals::DIMENSIONS)], 'weights.*' => ['integer', 'between:-2,2']])->validate()['weights'];
            $weights = array_filter($weights, fn ($w) => $w !== 0);
        }

        $outcome = DB::transaction(fn () => $this->apply($id, $weights, $actor), 3);
        // Raised after commit, so an outdated suggestion stays marked as such.
        abort_if($outcome['result'] === 'missing', 404);
        abort_if($outcome['result'] === 'skipped', 409, 'Dit voorstel is al beoordeeld.');
        abort_if($outcome['result'] === 'outdated', 409, 'De stelling is gewijzigd of al handmatig gewogen. Dit voorstel is niet toegepast.');

        return $outcome;
    }

    /** Approves each id independently; a changed or already-reviewed statement is reported, not forced. */
    public function approveMany(array $payload, ?int $actor): array
    {
        $data = Validator::make($payload, ['ids' => ['required_without:confidence', 'array', 'max:250'], 'ids.*' => ['integer'], 'confidence' => ['required_without:ids', Rule::in(['high', 'medium'])], 'limit' => ['nullable', 'integer', 'between:1,250']])->validate();
        $ids = $data['ids'] ?? DB::table('wdnl_compass_proposals')->where('status', 'pending')->where('confidence', $data['confidence'])->orderBy('id')->limit($data['limit'] ?? 100)->pluck('id')->all();
        $result = ['approved' => 0, 'outdated' => 0, 'skipped' => 0];
        foreach ($ids as $id) {
            $outcome = DB::transaction(fn () => $this->apply((int) $id, null, $actor), 3);
            $result[$outcome['result'] === 'missing' ? 'skipped' : $outcome['result']]++;
        }

        return $result + ['remaining' => DB::table('wdnl_compass_proposals')->where('status', 'pending')->count()];
    }

    public function reject(int $id, ?int $actor): array
    {
        return DB::transaction(function () use ($id, $actor) {
            $p = DB::table('wdnl_compass_proposals')->where('id', $id)->lockForUpdate()->first();
            abort_unless($p, 404);
            abort_if($p->status !== 'pending', 409, 'Dit voorstel is al beoordeeld.');
            DB::table('wdnl_compass_proposals')->where('id', $id)->update(['status' => 'rejected', 'reviewed_by' => $actor, 'reviewed_at' => now(), 'updated_at' => now()]);

            return ['result' => 'rejected', 'id' => $id];
        }, 3);
    }

    private function apply(int $id, ?array $weights, ?int $actor): array
    {
        $p = DB::table('wdnl_compass_proposals')->where('id', $id)->lockForUpdate()->first();
        if (! $p || $p->status !== 'pending') {
            return ['result' => $p ? 'skipped' : 'missing', 'id' => $id];
        }
        $issue = Issue::whereKey($p->issue_id)->lockForUpdate()->first(['id', 'slug', 'title', 'description', 'more_info']);
        $meta = DB::table('wdnl_statement_metadata')->where('issue_id', $p->issue_id)->lockForUpdate()->first();
        $hash = CompassProposals::textHash($issue);
        if (! hash_equals($p->text_hash, $hash) || CompassProposals::reviewedForCurrentText($meta, $hash)) {
            // Text edited after the suggestion, or someone already weighed it manually: never overwrite.
            DB::table('wdnl_compass_proposals')->where('id', $id)->update(['status' => 'outdated', 'updated_at' => now()]);

            return ['result' => 'outdated', 'id' => $id];
        }
        $weights ??= json_decode($p->weights, true) ?: [];
        $context = ['canonical_key' => null, 'rationale' => $p->rationale, 'topics' => json_decode($p->topics, true), 'historical' => false,
            'catalogue' => CompassProposals::VERSION, 'basis' => 'statement_wording', 'text_hash' => $hash];
        $data = ['compass_weights' => json_encode((object) $weights, JSON_THROW_ON_ERROR), 'compass_context' => json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'compass_reviewed_at' => now(), 'revision' => ($meta->revision ?? 0) + 1, 'updated_at' => now()];
        if ($meta) {
            DB::table('wdnl_statement_metadata')->where('issue_id', $issue->id)->update($data);
        } else {
            DB::table('wdnl_statement_metadata')->insert($data + ['issue_id' => $issue->id, 'created_at' => now()]);
        }
        DB::table('wdnl_compass_proposals')->where('id', $id)->update(['status' => 'approved', 'weights' => json_encode((object) $weights), 'reviewed_by' => $actor, 'reviewed_at' => now(), 'updated_at' => now()]);
        DB::table('wdnl_content_audits')->insert(['action' => 'compass.proposal.approve', 'token_id' => $actor, 'resource_key' => $issue->slug,
            'before' => $meta ? json_encode(['weights' => $meta->compass_weights], JSON_THROW_ON_ERROR) : null,
            'after' => json_encode(['weights' => $weights, 'context' => $context, 'proposal' => $id], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => now()]);

        return ['result' => 'approved', 'id' => $id, 'issue_id' => $issue->id, 'weights' => (object) $weights];
    }
}
