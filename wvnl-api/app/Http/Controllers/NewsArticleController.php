<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\NewsArticle;
use App\Services\NewsArticleService;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class NewsArticleController extends Controller
{
    public function __construct(private readonly NewsArticleService $service)
    {
    }

    public function index(Request $request)
    {
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 24);
        $limit = max(1, min($limit, 50));

        $articles = NewsArticle::query()
            ->with('issue:id,title,slug')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhereHas('issue', fn($q) => $q->where('title', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();

        return response()->json($articles->map(fn($article) => $this->serializeArticle($article)));
    }

    public function show(string $slug)
    {
        $article = NewsArticle::with('issue')->where('slug', $slug)->first();
        if (!$article) {
            $issue = Issue::where('news_article_slug', $slug)
                ->orWhere('slug', $slug)
                ->first();

            if (!$issue) {
                abort(404);
            }

            try {
                $article = $this->service->ensureArticleForIssue($issue);
            } catch (RuntimeException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $article->loadMissing('issue');
        }

        return response()->json($this->serializeArticle($article));
    }

    public function showByIssue(Issue $issue)
    {
        try {
            $article = $this->service->ensureArticleForIssue($issue);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $article->loadMissing('issue');

        return response()->json($this->serializeArticle($article));
    }

    public function generateForIssue(Issue $issue)
    {
        try {
            $article = $this->service->ensureArticleForIssue($issue);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $article->loadMissing('issue');

        return response()->json($this->serializeArticle($article));
    }

    protected function serializeArticle(NewsArticle $article): array
    {
        return [
            'id' => $article->id,
            'issue_id' => $article->issue_id,
            'slug' => $article->slug,
            'title' => $article->title,
            'subtitle' => $article->subtitle,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'sections' => $article->metadata['sections'] ?? [],
            'call_to_action' => $article->metadata['call_to_action'] ?? null,
            'generated_at' => $article->generated_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'issue' => $article->issue ? [
                'id' => $article->issue->id,
                'title' => $article->issue->title,
                'slug' => $article->issue->slug,
            ] : null,
        ];
    }
}
