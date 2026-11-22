<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\PoliticalParty;
use App\Services\CmsContentService;
use App\Services\CmsMotionSource;
use App\Services\CmsMotionStatements;
use App\Services\CmsPartySync;
use App\Services\Reports\CompassEditorialSeed;
use App\Services\Reports\CompassProposalReview;
use App\Services\Reports\CompassProposals;
use App\Services\Reports\CompassWeightsFile;
use App\Support\PoliticalPartyCatalogue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function __construct(private CmsContentService $content) {}

    public function overview()
    {
        return response()->json([
            'parties' => PoliticalParty::orderBy('name')->get()->map(fn ($p) => $this->content->party($p)),
            'statements_count' => Issue::count(), 'motions_count' => DB::table('wdnl_motions')->count(),
            'catalogue_date' => '2026-11-07',
            'recent_actions' => DB::table('wdnl_content_audits')->select('id', 'action', 'resource_key', 'created_at')->latest('id')->limit(10)->get(),
        ]);
    }

    public function statements(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $page = Issue::query()->when($data['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc('id')->paginate(20, ['id', 'title', 'slug', 'updated_at']);

        return response()->json($page);
    }

    public function statement(Issue $issue)
    {
        return response()->json($this->content->statement($issue));
    }

    public function motions(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000'], 'drafts' => ['sometimes', 'boolean']]);
        $page = DB::table('wdnl_motions')->leftJoin('wdnl_motion_statement_drafts as drafts', 'wdnl_motions.id', '=', 'drafts.motion_id')
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhere('number', 'like', '%'.$s.'%')))
            ->when($data['drafts'] ?? false, fn ($q) => $q->whereNotNull('drafts.motion_id'))
            ->orderByDesc('source_date')->orderByDesc('wdnl_motions.id')->paginate(20, ['wdnl_motions.*', 'drafts.motion_id as statement_draft_id']);

        return response()->json($page);
    }

    public function prepareMotionStatements(Request $request, CmsMotionStatements $statements)
    {
        $data = $request->validate(['limit' => ['required', 'integer', 'min:1', 'max:10']]);

        return response()->json(['motions' => $statements->prepare($data['limit'], $request->attributes->get('cms_token_id'))]);
    }

    public function generateMotionStatement(Request $request, CmsMotionStatements $statements, int $motionId)
    {
        return response()->json($statements->generate($motionId, $request->attributes->get('cms_token_id')));
    }

    public function motionStatementDraft(CmsMotionStatements $statements, int $motionId)
    {
        return response()->json($statements->draft($motionId));
    }

    public function saveParty(Request $request, ?PoliticalParty $party = null)
    {
        return response()->json($this->content->saveParty($request->all(), $party?->id, $request->attributes->get('cms_token_id')));
    }

    public function seedCompass(Request $request)
    {
        return response()->json(app(CompassEditorialSeed::class)->run($request->attributes->get('cms_token_id')));
    }

    public function compassProposals(Request $request, CompassProposalReview $review)
    {
        return response()->json($review->list($request->only(['status', 'confidence', 'page'])));
    }

    /** One click in zschotcms: a small, cost-capped batch with the model from the report settings. */
    public function generateCompassProposals(Request $request, CompassProposals $proposals)
    {
        $data = $request->validate(['limit' => ['required', 'integer', 'min:1', 'max:50']]);
        $lock = Cache::lock('wdnl:cms:compass-proposals', 300);
        abort_unless($lock->get(), 409, 'Er worden al kompasvoorstellen gemaakt.');
        set_time_limit(150); // about fifteen seconds per model batch
        try {
            return response()->json($proposals->run($data['limit'], 25, 0.10));
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        } finally {
            $lock->release();
        }
    }

    public function approveCompassProposal(Request $request, CompassProposalReview $review, int $id)
    {
        $weights = $request->input('weights');

        return response()->json($review->approve($id, is_array($weights) ? $weights : null, $request->attributes->get('cms_token_id')));
    }

    public function approveCompassProposals(Request $request, CompassProposalReview $review)
    {
        return response()->json($review->approveMany($request->only(['ids', 'confidence', 'limit']), $request->attributes->get('cms_token_id')));
    }

    public function rejectCompassProposal(Request $request, CompassProposalReview $review, int $id)
    {
        return response()->json($review->reject($id, $request->attributes->get('cms_token_id')));
    }

    public function exportCompassWeights(CompassWeightsFile $file)
    {
        return response()->json($file->export());
    }

    public function importCompassWeights(Request $request, CompassWeightsFile $file)
    {
        return response()->json($file->apply($request->only(['version', 'rows']), $request->attributes->get('cms_token_id')));
    }

    public function syncParties(Request $request, CmsPartySync $sync)
    {
        $lock = Cache::lock('wdnl:cms:party-sync', 120);
        abort_unless($lock->get(), 409, 'De partijen worden al gesynchroniseerd.');
        try {
            return response()->json($sync->sync($request->attributes->get('cms_token_id')));
        } finally {
            $lock->release();
        }
    }

    public function seedParties(Request $request)
    {
        return response()->json(DB::transaction(function () use ($request) {
            $before = PoliticalParty::orderBy('id')->get()->map(fn ($p) => $this->content->party($p))->all();
            $result = PoliticalPartyCatalogue::seed();
            $after = PoliticalParty::orderBy('id')->get()->map(fn ($p) => $this->content->party($p))->all();
            $this->content->audit('parties.seed', 'catalogue-2026-11-07', $before, $after, $request->attributes->get('cms_token_id'));

            return $result;
        }));
    }

    public function saveStatement(Request $request)
    {
        return response()->json($this->content->saveStatement($request->all(), $request->attributes->get('cms_token_id')));
    }

    public function saveMotion(Request $request)
    {
        return response()->json($this->content->saveMotion($request->all(), $request->attributes->get('cms_token_id')));
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'parties' => ['sometimes', 'array', 'max:50'], 'parties.*' => ['array'],
            'statements' => ['sometimes', 'array', 'max:25'], 'statements.*' => ['array'],
            'motions' => ['sometimes', 'array', 'max:25'], 'motions.*' => ['array'],
        ]);
        abort_unless(count($data['parties'] ?? []) + count($data['statements'] ?? []) + count($data['motions'] ?? []) > 0, 422, 'Kies ten minste één partij, stelling of motie.');
        $actor = $request->attributes->get('cms_token_id');

        // One transaction, including validation: failure on item 25 leaves items 1–24 untouched.
        return response()->json(DB::transaction(function () use ($data, $actor) {
            $result = ['parties' => [], 'motions' => [], 'statements' => []];
            foreach ($data['parties'] ?? [] as $row) {
                $id = PoliticalParty::where('slug', $row['slug'] ?? '')->value('id');
                $result['parties'][] = $this->content->saveParty($row, $id, $actor);
            }
            foreach ($data['motions'] ?? [] as $row) {
                $result['motions'][] = $this->content->saveMotion($row, $actor);
            }
            foreach ($data['statements'] ?? [] as $row) {
                $result['statements'][] = $this->content->saveStatement($row, $actor);
            }

            return $result;
        }, 3));
    }

    public function syncMotions(Request $request, CmsMotionSource $source, CmsMotionStatements $statements)
    {
        $data = $request->validate(['limit' => ['required', 'integer', 'min:1', 'max:25']]);
        $lock = Cache::lock('wdnl:cms:motion-sync', 30);
        abort_unless($lock->get(), 409, 'Er wordt al een motiebatch opgehaald.');
        try {
            $rows = $source->recent($data['limit']);

            $result = DB::transaction(function () use ($rows, $request) {
                $before = DB::table('wdnl_motions')->count();
                foreach ($rows as $row) {
                    $this->content->saveMotion($row, $request->attributes->get('cms_token_id'), sourceSync: true);
                }

                return ['fetched' => count($rows), 'created' => DB::table('wdnl_motions')->count() - $before];
            });
            $statements->refreshVotes($request->attributes->get('cms_token_id'));

            return response()->json($result);
        } finally {
            $lock->release();
        }
    }
}
