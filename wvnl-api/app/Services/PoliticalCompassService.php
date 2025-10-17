<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\PoliticalCompass;
use App\Models\PoliticalParty;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class PoliticalCompassService
{
    private const MAX_ISSUES = 25;
    private const MINIMUM_VOTES = 10;

    /** @var int[] */
    private const ALLOWED_PARTY_IDS = [
        16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30,
    ];

    private const IDEOLOGY_OPTIONS = [
        ['term' => 'Socialisme', 'hoofdkenmerk' => 'Gelijkheid, sociale rechtvaardigheid, sterke overheid voor herverdeling en sociale voorzieningen.', 'spectrum' => 'Links'],
        ['term' => 'Sociaaldemocratie', 'hoofdkenmerk' => 'Gematigde vorm van socialisme; combineert markt en welvaartsstaat.', 'spectrum' => 'Centrumlinks'],
        ['term' => 'Communisme', 'hoofdkenmerk' => 'Klasseloze en staatloze maatschappij, gemeenschappelijk bezit van productiemiddelen.', 'spectrum' => 'Extreemlinks'],
        ['term' => 'Liberalisme', 'hoofdkenmerk' => 'Individuele vrijheid, vrije markt, kleine overheid.', 'spectrum' => 'Rechts'],
        ['term' => 'Progressief', 'hoofdkenmerk' => 'Vooruitstrevend, openstaan voor verandering en vernieuwing van tradities.', 'spectrum' => 'Cultureel Boven (Verandering)'],
        ['term' => 'Conservatief', 'hoofdkenmerk' => 'Behoudend, hechten aan bestaande tradities en waarden.', 'spectrum' => 'Cultureel Onder (Behoud)'],
        ['term' => 'Christendemocratie', 'hoofdkenmerk' => 'Politiek gebaseerd op christelijke waarden; nadruk op rentmeesterschap en gemeenschap.', 'spectrum' => 'Centrum/Centrumrechts'],
        ['term' => 'Ecologisme', 'hoofdkenmerk' => 'Milieubescherming, duurzaamheid en het ecosysteem centraal.', 'spectrum' => 'Vaak Links/Progressief'],
        ['term' => 'Populisme', 'hoofdkenmerk' => "Benadrukt de kloof tussen 'het volk' en 'de elite'; kan inhoudelijk zowel links als rechts zijn.", 'spectrum' => 'Dwars door het spectrum'],
        ['term' => 'Fascisme/Nationaal-socialisme', 'hoofdkenmerk' => 'Autoritair, sterk nationalisme, afwijzing van democratie en liberalisme.', 'spectrum' => 'Extreemrechts'],
        ['term' => 'Anarchisme', 'hoofdkenmerk' => 'Streeft naar een samenleving zonder staatsgezag of hiërarchie.', 'spectrum' => 'Extreem (Links of Rechts)'],
        ['term' => 'Nationalisme', 'hoofdkenmerk' => 'Benadrukt de nationale identiteit en het belang van de eigen natie.', 'spectrum' => 'Overwegend Rechts'],
        ['term' => 'Centrisme', 'hoofdkenmerk' => 'Zoekt het midden, vermijdt extreme standpunten, pragmatisch beleid.', 'spectrum' => 'Midden'],
    ];

    public function minimumVotes(): int
    {
        return self::MINIMUM_VOTES;
    }

    public function canGenerate(User $user): bool
    {
        $latest = $user->politicalCompasses()->latest('created_at')->first();
        if (!$latest) {
            return true;
        }

        return $latest->created_at?->addDay()->isPast() ?? true;
    }

    public function nextAvailableAt(User $user): ?Carbon
    {
        $latest = $user->politicalCompasses()->latest('created_at')->first();
        if (!$latest || !$latest->created_at) {
            return null;
        }

        return $latest->created_at->copy()->addDay();
    }

    public function generate(User $user): PoliticalCompass
    {
        [$issues, $summary] = $this->prepareIssuePayload($user);
        if (empty($issues)) {
            throw new RuntimeException('Er zijn onvoldoende stemmen om een politiek kompas te genereren.');
        }

        $allowedParties = $this->loadAllowedParties();
        if ($allowedParties->isEmpty()) {
            throw new RuntimeException('Er zijn geen politieke partijen beschikbaar voor aanbeveling.');
        }

        $payload = [
            'user' => [
                'id' => (int) $user->id,
                'political_preference' => $user->political_preference,
                'total_votes' => count($issues),
                'votes_summary' => $summary,
            ],
            'votes' => $issues,
            'ideology_options' => self::IDEOLOGY_OPTIONS,
            'recommendable_parties' => $allowedParties->values()->map(function (PoliticalParty $party) {
                return [
                    'id' => (int) $party->id,
                    'name' => $party->name,
                    'abbreviation' => $party->abbreviation,
                    'slug' => $party->slug,
                    'website_url' => $party->website_url,
                ];
            })->all(),
        ];

        $analysis = $this->requestAnalysis($payload);
        $result = $this->parseAnalysis($analysis, $allowedParties);

        $compass = $user->politicalCompasses()->create([
            'stemgedrag_score' => $result['stemgedrag_score'],
            'label_term' => $result['politieke_label']['term'],
            'label_hoofdkenmerk' => $result['politieke_label']['hoofdkenmerk'] ?? null,
            'label_spectrum' => $result['politieke_label']['spectrum'] ?? null,
            'recommended_party_id' => $result['aanbevolen_partij']['id'],
            'recommended_party_motivation' => $result['aanbevolen_partij']['motivatie'],
            'analysis' => $analysis,
        ]);

        return $compass->load('recommendedParty');
    }

    public function serializeCompass(PoliticalCompass $compass): array
    {
        $compass->loadMissing('recommendedParty');

        return [
            'id' => $compass->id,
            'stemgedrag_score' => $compass->stemgedrag_score,
            'politieke_label' => [
                'term' => $compass->label_term,
                'hoofdkenmerk' => $compass->label_hoofdkenmerk,
                'spectrum' => $compass->label_spectrum,
            ],
            'aanbevolen_partij' => $compass->recommendedParty ? [
                'id' => $compass->recommendedParty->id,
                'name' => $compass->recommendedParty->name,
                'abbreviation' => $compass->recommendedParty->abbreviation,
                'slug' => $compass->recommendedParty->slug,
                'logo_url' => $compass->recommendedParty->logo_url,
                'website_url' => $compass->recommendedParty->website_url,
                'motivatie' => $compass->recommended_party_motivation,
            ] : null,
            'created_at' => $compass->created_at?->toIso8601String(),
            'updated_at' => $compass->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>}
     */
    protected function prepareIssuePayload(User $user): array
    {
        $issueIds = array_values(array_map('intval', (array) ($user->voted_issue_ids ?? [])));
        if (empty($issueIds)) {
            return [[], ['agree' => 0, 'disagree' => 0, 'neutral' => 0]];
        }

        $issueIds = array_slice($issueIds, -self::MAX_ISSUES);

        $issues = Issue::whereIn('id', $issueIds)->get()->keyBy('id');

        $allPartyIds = $issues->flatMap(function (Issue $issue) {
            $stances = $issue->party_stances ?? [];
            return collect($stances)
                ->flatMap(fn($ids) => array_map('intval', (array) $ids));
        })->unique()->values();

        $partyMap = PoliticalParty::whereIn('id', $allPartyIds)->get()->keyBy('id');

        $orderedIssues = [];
        $summary = ['agree' => 0, 'disagree' => 0, 'neutral' => 0];

        foreach ($issueIds as $issueId) {
            /** @var Issue|null $issue */
            $issue = $issues->get($issueId);
            if (!$issue) {
                continue;
            }

            $vote = $this->determineUserVote($issue, (int) $user->id);
            if (!$vote) {
                continue;
            }

            $summary[$vote]++;

            $stances = (array) ($issue->party_stances ?? []);

            $orderedIssues[] = [
                'issue_id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'url' => $issue->url,
                'user_vote' => $vote,
                'user_vote_label' => $this->voteLabel($vote),
                'party_support' => [
                    'agree' => $this->mapPartiesForStance($stances['agree'] ?? [], $partyMap),
                    'neutral' => $this->mapPartiesForStance($stances['neutral'] ?? [], $partyMap),
                    'disagree' => $this->mapPartiesForStance($stances['disagree'] ?? [], $partyMap),
                ],
            ];
        }

        return [$orderedIssues, $summary];
    }

    protected function determineUserVote(Issue $issue, int $userId): ?string
    {
        $votes = $issue->votes ?? ['agree' => [], 'disagree' => [], 'neutral' => []];
        foreach (['agree', 'disagree', 'neutral'] as $bucket) {
            $ids = array_map('intval', (array) ($votes[$bucket] ?? []));
            if (in_array($userId, $ids, true)) {
                return $bucket;
            }
        }

        return null;
    }

    /**
     * @param array<int|string> $partyIds
     * @param Collection<int, PoliticalParty> $partyMap
     * @return array<int, array<string, mixed>>
     */
    protected function mapPartiesForStance(array $partyIds, Collection $partyMap): array
    {
        $result = [];
        foreach ($partyIds as $id) {
            $party = $partyMap->get((int) $id);
            if (!$party) {
                continue;
            }

            $result[] = [
                'id' => (int) $party->id,
                'name' => $party->name,
                'abbreviation' => $party->abbreviation,
            ];
        }

        return $result;
    }

    protected function voteLabel(string $vote): string
    {
        return match ($vote) {
            'agree' => 'Voor',
            'disagree' => 'Tegen',
            default => 'Neutraal',
        };
    }

    protected function loadAllowedParties(): Collection
    {
        return PoliticalParty::whereIn('id', self::ALLOWED_PARTY_IDS)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function requestAnalysis(array $payload): array
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is niet geconfigureerd.');
        }

        $model = config('services.openai.model', 'gpt-4.1-mini');
        $organization = config('services.openai.organization');

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
                        'content' => 'Je bent een politiek data-analist. Analyseer stemgedrag van Nederlandse kiezers en geef een bondig, feitelijk advies. Houd je strikt aan het JSON-schema. Gebruik alleen de aangeleverde gegevens.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildUserPrompt($payload),
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => 700,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'political_compass',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['stemgedrag_score', 'politieke_label', 'aanbevolen_partij'],
                            'properties' => [
                                'stemgedrag_score' => [
                                    'type' => 'integer',
                                    'minimum' => 1,
                                    'maximum' => 10,
                                ],
                                'politieke_label' => [
                                    'type' => 'object',
                                    'required' => ['term', 'hoofdkenmerk', 'spectrum'],
                                    'properties' => [
                                        'term' => [
                                            'type' => 'string',
                                            'enum' => array_map(fn($option) => $option['term'], self::IDEOLOGY_OPTIONS),
                                        ],
                                        'hoofdkenmerk' => [
                                            'type' => 'string',
                                        ],
                                        'spectrum' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                                'aanbevolen_partij' => [
                                    'type' => 'object',
                                    'required' => ['id', 'motivatie'],
                                    'properties' => [
                                        'id' => [
                                            'type' => 'integer',
                                            'enum' => self::ALLOWED_PARTY_IDS,
                                        ],
                                        'motivatie' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI gaf geen geldig antwoord terug.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Kon het OpenAI-antwoord niet verwerken: ' . $exception->getMessage(), 0, $exception);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $analysis
     * @param Collection<int, PoliticalParty> $allowedParties
     * @return array<string, mixed>
     */
    protected function parseAnalysis(array $analysis, Collection $allowedParties): array
    {
        $score = (int) Arr::get($analysis, 'stemgedrag_score');
        if ($score < 1 || $score > 10) {
            throw new RuntimeException('OpenAI gaf een ongeldige stemgedrag-score terug.');
        }

        $label = Arr::get($analysis, 'politieke_label');
        if (!is_array($label)) {
            throw new RuntimeException('OpenAI gaf geen geldig label terug.');
        }

        $term = Arr::get($label, 'term');
        $validTerms = array_map(fn($option) => $option['term'], self::IDEOLOGY_OPTIONS);
        if (!is_string($term) || !in_array($term, $validTerms, true)) {
            throw new RuntimeException('OpenAI koos een ongeldig politiek label.');
        }

        $party = Arr::get($analysis, 'aanbevolen_partij');
        if (!is_array($party)) {
            throw new RuntimeException('OpenAI gaf geen geldige partij terug.');
        }

        $partyId = (int) Arr::get($party, 'id');
        if (!$allowedParties->has($partyId)) {
            throw new RuntimeException('OpenAI koos een partij die niet aanbevolen mag worden.');
        }

        $motivation = trim((string) Arr::get($party, 'motivatie'));
        if ($motivation === '') {
            throw new RuntimeException('OpenAI gaf geen motivatie voor de aanbevolen partij.');
        }

        return [
            'stemgedrag_score' => $score,
            'politieke_label' => [
                'term' => $term,
                'hoofdkenmerk' => Arr::get($label, 'hoofdkenmerk'),
                'spectrum' => Arr::get($label, 'spectrum'),
            ],
            'aanbevolen_partij' => [
                'id' => $partyId,
                'motivatie' => $motivation,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function buildUserPrompt(array $payload): string
    {
        $instructions = [
            'Analyseer het stemgedrag van de gebruiker op basis van de aangeleverde data.',
            'Geef een score van 1 (links) tot 10 (rechts); 5 is exact het midden.',
            'Kies precies één label uit de lijst "ideology_options" en gebruik dezelfde term, hoofdkenmerk en spectrum als referentie.',
            'Kies één partij-id uit "recommendable_parties" en motiveer de keuze in maximaal 80 woorden.',
            'Noem geen partijen of labels buiten de lijsten en speculeer niet.',
            'Formuleer alle tekst in het Nederlands.',
        ];

        return implode("\n", $instructions)
            . "\n\nGegevens:\n"
            . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
