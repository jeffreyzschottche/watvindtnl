<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\NewsArticle;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NewsArticleService
{
    public function ensureArticleForIssue(Issue $issue): NewsArticle
    {
        if ($issue->relationLoaded('newsArticle') && $issue->newsArticle) {
            return $issue->newsArticle;
        }

        $article = NewsArticle::where('issue_id', $issue->id)->first();
        if ($article) {
            $issue->setRelation('newsArticle', $article);
            if (!$issue->news_article_slug) {
                $issue->news_article_slug = $article->slug;
                $issue->save();
            }

            return $article;
        }

        $payload = $this->requestArticle($issue);

        $slug = $this->buildSlug($issue, $payload);

        $article = NewsArticle::create([
            'issue_id' => $issue->id,
            'title' => $payload['title'] ?? $issue->title,
            'slug' => $slug,
            'subtitle' => $payload['subtitle'] ?? null,
            'excerpt' => $payload['excerpt'] ?? null,
            'body' => $this->composeBody($payload),
            'metadata' => [
                'sections' => $payload['sections'] ?? [],
                'call_to_action' => $payload['call_to_action'] ?? null,
                'source' => 'openai',
            ],
            'generated_at' => now(),
        ]);

        $issue->news_article_slug = $article->slug;
        $issue->save();
        $issue->setRelation('newsArticle', $article);

        return $article;
    }

    protected function buildSlug(Issue $issue, array $payload): string
    {
        $base = Str::slug($payload['slug'] ?? $payload['title'] ?? $issue->slug ?? $issue->title);
        if (!$base) {
            $base = 'issue-' . $issue->id;
        }

        $slug = $base;
        $counter = 2;
        while (NewsArticle::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function composeBody(array $payload): string
    {
        $sections = $payload['sections'] ?? [];
        if (!is_array($sections) || empty($sections)) {
            return trim((string) ($payload['body'] ?? $payload['excerpt'] ?? ''));
        }

        $content = collect($sections)
            ->filter(fn($section) => isset($section['heading']) || isset($section['content']))
            ->map(function ($section) {
                $heading = trim((string) ($section['heading'] ?? ''));
                $body = trim((string) ($section['content'] ?? ''));

                $parts = [];
                if ($heading) {
                    $parts[] = "## {$heading}";
                }
                if ($body) {
                    $parts[] = $body;
                }

                return implode("\n\n", $parts);
            })
            ->filter()
            ->implode("\n\n");

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestArticle(Issue $issue): array
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY ontbreekt.');
        }

        $model = config('services.openai.model', 'gpt-4.1-mini');
        $organization = config('services.openai.organization');

        $prompt = $this->buildPrompt($issue);

        $headers = ['Authorization' => 'Bearer ' . $apiKey];
        if ($organization) {
            $headers['OpenAI-Organization'] = $organization;
        }

        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Je bent een politieke redacteur. Schrijf Nederlandstalige artikelen over moties van de Tweede Kamer. Gebruik korte zinnen en leg vakjargon uit.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.35,
                'max_tokens' => 900,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'issue_news_article',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['title', 'excerpt', 'sections'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'slug' => ['type' => 'string'],
                                'subtitle' => ['type' => 'string'],
                                'excerpt' => ['type' => 'string'],
                                'sections' => [
                                    'type' => 'array',
                                    'minItems' => 2,
                                    'items' => [
                                        'type' => 'object',
                                        'required' => ['heading', 'content'],
                                        'properties' => [
                                            'heading' => ['type' => 'string'],
                                            'content' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                                'call_to_action' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI kon geen artikel leveren: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            throw new RuntimeException('OpenAI gaf een leeg antwoord terug.');
        }

        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (is_array($content)) {
            return $content;
        }

        throw new RuntimeException('OpenAI antwoord kon niet worden verwerkt.');
    }

    protected function buildPrompt(Issue $issue): string
    {
        $parts = [
            'Schrijf een helder artikel van maximaal 350 woorden over de volgende motie.',
            'Structuur:',
            '- Korte intro waarin de kern genoemd wordt.',
            '- Ten minste twee tussenkoppen: "Wat staat er in de motie?" en "Waarom is dit belangrijk?".',
            '- Gebruik eenvoudige taal en leg moeilijke termen uit.',
            '- Sluit af met een oproep tot actie (uitnodiging om in onze app te stemmen).',
            '',
            'Gegevens van de motie:',
            '- Titel: ' . $issue->title,
        ];

        if ($issue->description) {
            $parts[] = '- Beschrijving: ' . $issue->description;
        }

        if ($issue->more_info) {
            $parts[] = '- Extra context: ' . $issue->more_info;
        }

        if ($issue->url) {
            $parts[] = '- Brondocument: ' . $issue->url;
        }

        return implode("\n", $parts);
    }
}
