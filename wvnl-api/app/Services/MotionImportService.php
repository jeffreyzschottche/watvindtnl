<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Admin\AdminIssueController;
use App\Models\Issue;
use App\Models\PoliticalParty;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MotionImportService
{
    private const PARTY_SYNONYMS = [
        'pvv' => 'PVV',
        'partij voor de vrijheid' => 'PVV',
        'groenlinks-pvda' => 'GL-PvdA',
        'gl-pvda' => 'GL-PvdA',
        'groenlinks' => 'GL-PvdA',
        'pvda' => 'GL-PvdA',
        'pvdA' => 'GL-PvdA',
        'volkspartij voor vrijheid en democratie' => 'VVD',
        'vvd' => 'VVD',
        'nieuw sociaal contract' => 'NSC',
        'nsc' => 'NSC',
        'democraten 66' => 'D66',
        'd66' => 'D66',
        'boerburgerbeweging' => 'BBB',
        'bbb' => 'BBB',
        'christen-democratisch appèl' => 'CDA',
        'christen-democratisch appel' => 'CDA',
        'cda' => 'CDA',
        'socialistische partij' => 'SP',
        'sp' => 'SP',
        'denk' => 'DENK',
        'partij voor de dieren' => 'PvdD',
        'pvdd' => 'PvdD',
        'forum voor democratie' => 'FvD',
        'fvd' => 'FvD',
        'staatkundig gereformeerde partij' => 'SGP',
        'sgp' => 'SGP',
        'christenunie' => 'CU',
        'christen unie' => 'CU',
        'cu' => 'CU',
        'volt nederland' => 'Volt',
        'volt' => 'Volt',
        'ja21' => 'JA21',
    ];

    private string $baseUrl;
    private string $statePath;
    private string $workDir;
    private string $outputDir;
    private int $requestDelay;
    private string $openAiEndpoint;
    private string $openAiModel;
    private string $openAiKey;
    private ?string $openAiProject;

    /** @var array<string,int> */
    private array $partyIndex = [];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('motion-import.base_url'), '/');
        $this->statePath = (string) (config('motion-import.state_path') ?: storage_path('motions/state.json'));
        $this->workDir = (string) (config('motion-import.work_dir') ?: storage_path('motions/tmp'));
        $this->outputDir = (string) (config('motion-import.output_dir') ?: storage_path('motions/output'));
        $this->requestDelay = (int) config('motion-import.request_delay_microseconds', 150000);
        $openAi = (array) config('motion-import.openai', []);
        $this->openAiEndpoint = (string) ($openAi['endpoint'] ?? 'https://api.openai.com/v1/chat/completions');
        $this->openAiModel = (string) ($openAi['model'] ?? 'gpt-4.1-mini');
        $this->openAiKey = (string) (config('services.openai.key') ?? env('OPENAI_API_KEY', ''));
        $this->openAiProject = env('OPENAI_PROJECT');
    }

    /**
     * Haal de nieuwste moties op, genereer toegankelijke issues en importeer ze in de DB.
     *
     * @return array{count:int,issues:array,state:array}
     */
    public function sync(int $limit, bool $keepJsonFile = false): array
    {
        if ($limit < 1) {
            throw new RuntimeException('Batchgrootte moet minimaal 1 zijn.');
        }

        if (!$this->openAiKey) {
            throw new RuntimeException('OPENAI_API_KEY ontbreekt (stel deze in .env).');
        }

        $this->ensureDirectories();
        $this->hydratePartyIndex();

        $state = $this->readState();
        $lastProcessedId = $state['last_processed_id'] ?? null;

        $candidates = $this->discoverMotions($lastProcessedId, $limit);
        if (!count($candidates) && $lastProcessedId !== null) {
            $candidates = $this->discoverMotions(null, $limit);
        }
        if (!count($candidates)) {
            throw new RuntimeException('Geen nieuwe moties gevonden.');
        }

        $bundles = [];
        $errors = [];

        foreach ($candidates as $candidate) {
            try {
                $bundles[] = $this->buildMotionBundle($candidate);
                if ($this->requestDelay > 0) {
                    usleep($this->requestDelay);
                }
            } catch (Throwable $e) {
                $errors[] = sprintf(
                    '#%s (Id %d): %s',
                    $candidate['Nummer'] ?? '?',
                    $candidate['Id'] ?? 0,
                    $e->getMessage()
                );
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException("Moties konden niet worden voorbereid:\n" . implode("\n", $errors));
        }

        $issues = [];
        foreach ($bundles as $bundle) {
            $issues[] = $this->buildIssuePayload($bundle);
        }

        if (!count($issues)) {
            throw new RuntimeException('Geen issues gegenereerd uit de opgehaalde moties.');
        }

        $issues = $this->filterExistingIssues($issues);
        if (!count($issues)) {
            throw new RuntimeException('Alle opgehaalde moties bestaan al in de database.');
        }

        $payload = ['issues' => $issues];
        $outputFile = $this->writePayload($payload, $candidates);

        $importResponse = null;
        try {
            $importResponse = $this->importIssues($issues);
        } catch (Throwable $e) {
            $message = trim($e->getMessage()) ?: get_class($e);
            throw new RuntimeException(
                'Importeren van moties mislukt: ' . $message . '. Laatste JSON-bestand: ' . $outputFile,
                previous: $e
            );
        } finally {
            if (!$keepJsonFile && $importResponse !== null && file_exists($outputFile)) {
                @unlink($outputFile);
            }
        }

        $lastCandidate = end($candidates);
        $maxId = max(array_map(fn ($row) => (int) ($row['Id'] ?? 0), $candidates));
        $this->writeState([
            'last_processed_id' => $maxId,
            'last_processed_nummer' => $lastCandidate['Nummer'] ?? null,
            'processed_count' => count($issues),
            'updated_at' => Carbon::now()->toIso8601String(),
        ]);

        return [
            'count' => count($issues),
            'issues' => $importResponse,
            'state' => [
                'last_processed_id' => $maxId,
                'last_processed_nummer' => $lastCandidate['Nummer'] ?? null,
            ],
        ];
    }

    private function ensureDirectories(): void
    {
        foreach ([$this->workDir, $this->outputDir, dirname($this->statePath)] as $dir) {
            if (!$dir) {
                continue;
            }
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException("Kon map niet aanmaken: {$dir}");
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function readState(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }

        $content = (string) @file_get_contents($this->statePath);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeState(array $state): void
    {
        File::put(
            $this->statePath,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return array<int, array{Id:int, Nummer:string}>
     */
    private function discoverMotions(?int $afterId, int $limit): array
    {
        $filter = "Verwijderd eq false and Soort eq 'Motie'";
        $take = max($limit * 2, $limit + 5);

        $url = $this->buildUrl('/Zaak', [
            '$filter' => $filter,
            '$select' => 'Id,Nummer,Soort',
            '$orderby' => 'Id desc',
            '$top' => $take,
        ]);

        $json = $this->fetchJson($url);
        $values = is_array($json['value'] ?? null) ? $json['value'] : [];

        $motions = [];
        foreach ($values as $row) {
            if (!isset($row['Id'], $row['Nummer'])) {
                continue;
            }
            $motions[] = [
                'Id' => (int) $row['Id'],
                'Nummer' => (string) $row['Nummer'],
            ];
        }

        if ($afterId !== null) {
            $motions = array_filter($motions, fn ($row) => $row['Id'] > $afterId);
        }

        usort($motions, fn ($a, $b) => $a['Id'] <=> $b['Id']);

        return array_slice(array_values($motions), 0, $limit);
    }

    /**
     * @param array{Id:int, Nummer:string} $candidate
     */
    private function buildMotionBundle(array $candidate): array
    {
        $nummer = $this->normalizeNummer($candidate['Nummer']);
        $zaakResponse = $this->fetchZaak($nummer);

        $zaak = $zaakResponse['zaak'];
        $docResponse = $this->fetchDocument((string) $zaak['Id']);
        $document = $docResponse['document'];

        $publicationInfo = $docResponse['publication'] ?? null;
        $publicationUrl = $publicationInfo['Url'] ?? null;

        $pdfPath = null;
        $extractedText = null;
        $extractionNote = null;

        if ($document && isset($document['Id'])) {
            $pdfPath = $this->downloadPdf((string) $document['Id'], $nummer);
            if ($pdfPath) {
                [$extractedText, $extractionNote] = $this->extractPdfText($pdfPath);
                @unlink($pdfPath);
            }
        }

        return [
            'id' => (int) $candidate['Id'],
            'nummer' => $nummer,
            'zaak' => $zaak,
            'document' => $document,
            'publication' => $publicationInfo,
            'publication_url' => $publicationUrl,
            'source' => [
                'zaak_url' => $zaakResponse['url'],
                'document_url' => $docResponse['url'],
            ],
            'more_information' => $extractedText,
            'extraction_note' => $extractionNote,
        ];
    }

    private function fetchZaak(string $nummer): array
    {
        $url = $this->buildZaakUrl($nummer);
        $json = $this->fetchJson($url);
        $value = is_array($json['value'] ?? null) ? $json['value'] : [];
        $zaak = $value[0] ?? null;

        if (!$zaak) {
            throw new RuntimeException("Geen Zaak gevonden voor motie {$nummer}");
        }

        return [
            'zaak' => $zaak,
            'url' => $url,
        ];
    }

    private function fetchDocument(string $zaakId): array
    {
        $url = $this->buildZaakToDocumentUrl($zaakId);
        $json = $this->fetchJson($url);
        $value = is_array($json['value'] ?? null) ? $json['value'] : [];
        $document = $value[0] ?? null;

        $publication = null;
        if ($document) {
            $versies = $document['DocumentVersie'] ?? [];
            if (is_array($versies) && count($versies)) {
                $pubs = $versies[0]['DocumentPublicatie'] ?? [];
                if (is_array($pubs) && count($pubs)) {
                    $publication = $pubs[0];
                }
            }
        }

        return [
            'document' => $document,
            'publication' => $publication,
            'url' => $url,
        ];
    }

    private function downloadPdf(string $documentId, string $nummer): ?string
    {
        $url = "{$this->baseUrl}/Document({$documentId})/resource";
        $response = Http::timeout(120)
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException("Download van PDF voor motie {$nummer} mislukt (HTTP {$response->status()}).");
        }

        $filename = $this->workDir . '/motie-' . preg_replace('~[^A-Za-z0-9]+~', '-', $nummer) . '.pdf';
        File::put($filename, $response->body());

        return $filename;
    }

    private function buildIssuePayload(array $bundle): array
    {
        $zaak = $bundle['zaak'];
        $title = trim((string) ($zaak['Titel'] ?? ('Motie ' . $bundle['nummer'])));

        $stances = $this->buildPartyStances($zaak);
        $moreInformation = trim((string) ($bundle['more_information'] ?? ''));

        $aiPayload = $this->generateIssueWithAi([
            'title' => $title,
            'nummer' => $bundle['nummer'],
            'subject' => $zaak['Onderwerp'] ?? null,
            'party_stances' => $stances,
            'more_information' => $moreInformation,
            'besluit' => $zaak['Besluit'] ?? [],
            'document' => $bundle['document'],
            'publication' => $bundle['publication'],
            'source' => $bundle['source'],
        ]);

        $issue = $aiPayload['issues'][0] ?? [];
        if (!$issue) {
            throw new RuntimeException("Model-output bevat geen issue voor motie {$bundle['nummer']}.");
        }

        $description = $this->limitSentences(trim((string) ($issue['description'] ?? '')), 2);
        $moreInfo = $this->limitSentences(trim((string) ($issue['more_info'] ?? '')), 6);
        $slug = $this->normalizeSlug($issue['slug'] ?? null, $issue['title'] ?? $title, $bundle['nummer']);

        $arguments = $this->normalizeArguments($issue['arguments'] ?? []);
        $url = $this->pickIssueUrl($bundle);

        return [
            'title' => $issue['title'] ?? $title,
            'slug' => $slug,
            'url' => $url,
            'description' => $description ?: null,
            'more_info' => $moreInfo ?: null,
            'party_stances' => $stances,
            'arguments' => $arguments,
        ];
    }

    private function pickIssueUrl(array $bundle): ?string
    {
        $candidates = [
            $bundle['publication_url'] ?? null,
            $bundle['source']['document_url'] ?? null,
            $bundle['source']['zaak_url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $trimmed = trim((string) $candidate);
            if ($trimmed === '') {
                continue;
            }
            if (strlen($trimmed) <= 255) {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * @return array{issues:array}
     */
    private function generateIssueWithAi(array $context): array
    {
        $systemPrompt = <<<PROMPT
Je bent een JSON-transformeer-assistent voor Wat Denkt Nederland?.
Zet officiële motieteksten om naar begrijpelijke issues voor gewone mensen.
Regels:
- Antwoord uitsluitend met geldige JSON (geen uitleg buiten JSON).
- 'description' is de tekst op de issue-card: maximaal 2 korte zinnen in simpel Nederlands die direct uitleggen wat de motie doet (antwoord op "Wat houdt deze motie in?").
- 'more_info' vat de motie samen in 3 tot 6 beknopte zinnen; leg ingewikkelde termen kort uit.
- Bedenk 2 tot 4 pro- en 2 tot 4 contra-argumenten; wees concreet en neutraal.
- Gebruik de aangeleverde 'party_stances' ongewijzigd.
- Geen verzonnen feiten; blijf dicht bij de inhoud.
PROMPT;

        $schema = [
            'issues' => [[
                'title' => 'string',
                'slug' => 'kebab-case string gebaseerd op title',
                'description' => '1-2 korte zinnen in simpel Nederlands die uitleggen wat de motie doet',
                'more_info' => '3-6 zinnen met iets meer context',
                'party_stances' => $context['party_stances'],
                'arguments' => [
                    'pro' => [['side' => 'pro', 'body' => 'reden om voor te stemmen', 'sources' => ['optionele URL']]],
                    'con' => [['side' => 'con', 'body' => 'reden om tegen te stemmen', 'sources' => ['optionele URL']]],
                ],
            ]],
        ];

        $userContent = [
            'schema' => $schema,
            'input_meta' => [
                'title' => $context['title'],
                'nummer' => $context['nummer'],
                'subject' => $context['subject'],
            ],
            'party_stances' => $context['party_stances'],
            'more_information' => $context['more_information'],
            'bron_json' => [
                'Besluit' => $context['besluit'],
                'Document' => $context['document'],
                'Publication' => $context['publication'],
                'source' => $context['source'],
            ],
        ];

        $payload = [
            'model' => $this->openAiModel,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => json_encode($userContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
        ];

        $response = $this->callOpenAi($payload);

        $content = data_get($response, 'choices.0.message.content', '');
        $decoded = $this->decodeJsonFromString($content);

        if (!is_array($decoded)) {
            throw new RuntimeException('Model-output kon niet als JSON worden gelezen.');
        }

        return $decoded;
    }

    private function callOpenAi(array $payload): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->openAiKey,
            'Content-Type' => 'application/json',
        ];
        if ($this->openAiProject) {
            $headers['OpenAI-Project'] = $this->openAiProject;
        }

        $delay = 1.0;
        $maxRetries = 6;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(120)
                    ->post($this->openAiEndpoint, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        return $json;
                    }
                    throw new RuntimeException('Onverwachte AI-respons.');
                }

                if (!in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                    throw new RuntimeException(
                        'OpenAI HTTP-fout ' . $response->status() . ': ' . $response->body()
                    );
                }
            } catch (ConnectionException $e) {
                if ($attempt === $maxRetries) {
                    throw new RuntimeException('OpenAI-call mislukt: ' . $e->getMessage(), previous: $e);
                }
            }

            usleep((int) ($delay * 1_000_000));
            $delay = min(10.0, $delay * 1.8);
        }

        throw new RuntimeException('OpenAI-call kon niet worden voltooid.');
    }

    private function decodeJsonFromString(string $input): ?array
    {
        $trimmed = trim($input);
        $first = strpos($trimmed, '{');
        $last = strrpos($trimmed, '}');

        if ($first !== false && $last !== false) {
            $trimmed = substr($trimmed, $first, $last - $first + 1);
        }

        $decoded = json_decode($trimmed, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function buildPartyStances(array $zaak): array
    {
        $result = [
            'agree' => [],
            'neutral' => [],
            'disagree' => [],
        ];

        $besluiten = $zaak['Besluit'] ?? [];
        if (!is_array($besluiten)) {
            return $result;
        }

        foreach ($besluiten as $besluit) {
            $stemmingen = $besluit['Stemming'] ?? [];
            if (!is_array($stemmingen)) {
                continue;
            }

            foreach ($stemmingen as $stem) {
                $partyId = $this->mapPartyToId($stem['ActorFractie'] ?? null, $stem['ActorNaam'] ?? null);
                if (!$partyId) {
                    continue;
                }

                $soort = mb_strtolower((string) ($stem['Soort'] ?? ''), 'UTF-8');
                if ($soort === 'voor') {
                    $result['agree'][] = $partyId;
                } elseif ($soort === 'tegen') {
                    $result['disagree'][] = $partyId;
                } else {
                    $result['neutral'][] = $partyId;
                }
            }
        }

        foreach ($result as $key => $list) {
            $result[$key] = array_values(array_unique(array_map('intval', $list)));
        }

        $result['neutral'] = array_values(array_diff($result['neutral'], $result['agree'], $result['disagree']));
        $result['disagree'] = array_values(array_diff($result['disagree'], $result['agree']));

        sort($result['agree']);
        sort($result['neutral']);
        sort($result['disagree']);

        return $result;
    }

    private function mapPartyToId(?string $name, ?string $fractie): ?int
    {
        $key = mb_strtolower(trim((string) ($fractie ?: $name ?: '')), 'UTF-8');
        $key = str_replace(['groep ', 'fractie '], '', $key);
        $key = preg_replace('~\s+~', ' ', $key);

        if (!$key) {
            return null;
        }

        $abbr = self::PARTY_SYNONYMS[$key] ?? null;
        if (!$abbr) {
            return null;
        }

        $upper = mb_strtoupper($abbr, 'UTF-8');
        return $this->partyIndex[$upper] ?? null;
    }

    private function hydratePartyIndex(): void
    {
        if (!empty($this->partyIndex)) {
            return;
        }

        $parties = PoliticalParty::query()->get(['id', 'abbreviation']);
        foreach ($parties as $party) {
            if (!$party->abbreviation) {
                continue;
            }
            $this->partyIndex[mb_strtoupper($party->abbreviation, 'UTF-8')] = (int) $party->id;
        }
    }

    private function limitSentences(string $text, int $maxSentences): string
    {
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/(?<=[.!?])\s+/u', $text);
        if (!$parts) {
            return $text;
        }

        return trim(implode(' ', array_slice($parts, 0, $maxSentences)));
    }

    private function normalizeSlug(?string $slug, ?string $title, ?string $nummer): string
    {
        $candidate = trim((string) ($slug ?: $title ?: 'issue'));
        $normalized = Str::slug($candidate) ?: 'issue';

        $nummerSlug = $nummer ? Str::slug($nummer) : '';
        if ($nummerSlug !== '' && !str_contains($normalized, $nummerSlug)) {
            $normalized = rtrim($normalized, '-') . '-' . $nummerSlug;
        }

        return $normalized;
    }

    private function normalizeArguments(array $arguments): array
    {
        $result = [
            'pro' => [],
            'con' => [],
        ];

        foreach (['pro', 'con'] as $side) {
            $list = $arguments[$side] ?? [];
            if (!is_array($list)) {
                continue;
            }

            foreach ($list as $argument) {
                $body = trim((string) ($argument['body'] ?? ''));
                if ($body === '') {
                    continue;
                }

                $sources = $argument['sources'] ?? [];
                if (!is_array($sources)) {
                    $sources = [];
                }
                $sources = array_values(array_filter(array_map('strval', $sources), fn ($value) => trim($value) !== ''));

                $result[$side][] = [
                    'side' => $side,
                    'body' => $body,
                    'sources' => $sources,
                ];
            }
        }

        return $result;
    }

    private function filterExistingIssues(array $issues): array
    {
        if (!count($issues)) {
            return $issues;
        }

        $slugs = array_values(array_unique(array_map(fn ($issue) => $issue['slug'], $issues)));
        $existing = Issue::query()
            ->whereIn('slug', $slugs)
            ->pluck('slug')
            ->all();

        if (!count($existing)) {
            return $issues;
        }

        $existingMap = array_flip($existing);
        $filtered = array_values(array_filter($issues, function ($issue) use ($existingMap) {
            return !isset($existingMap[$issue['slug']]);
        }));

        return $filtered;
    }

    private function importIssues(array $issues): array
    {
        /** @var AdminIssueController $controller */
        $controller = app(AdminIssueController::class);
        $request = Request::create('/admin/issues/import', 'POST', ['issues' => $issues]);

        $response = $controller->bulkStore($request);
        $payload = $response->getData(true);

        return is_array($payload) ? $payload : [];
    }

    private function writePayload(array $payload, array $candidates): string
    {
        $minId = min(array_map(fn ($row) => (int) ($row['Id'] ?? 0), $candidates));
        $maxId = max(array_map(fn ($row) => (int) ($row['Id'] ?? 0), $candidates));

        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = sprintf('moties_%d-%d_%s.json', $minId, $maxId, $timestamp);
        $path = $this->outputDir . '/' . $filename;

        File::put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $path;
    }

    private function extractPdfText(string $pdfPath): array
    {
        if (!is_file($pdfPath)) {
            return [null, 'PDF-bestand ontbreekt.'];
        }

        if ($text = $this->runPdftotext($pdfPath, '-layout')) {
            return [$text, null];
        }

        if ($text = $this->runPdftotext($pdfPath, '-raw')) {
            return [$text, 'Tekst via pdftotext (-raw).'];
        }

        if ($text = $this->runMutool($pdfPath)) {
            return [$text, 'Tekst via mutool.'];
        }

        if ($text = $this->runOcrFallback($pdfPath)) {
            return [$text, 'Tekst via OCR (tesseract, eerste 10 pagina’s).'];
        }

        return [null, 'Geen tekstextractie mogelijk. Overweeg OCR tooling.'];
    }

    private function runPdftotext(string $pdfPath, string $mode): ?string
    {
        if (!$this->hasCommand('pdftotext')) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdftotext');
        if ($tmp === false) {
            return null;
        }
        @unlink($tmp);
        $txtPath = $tmp . '.txt';

        $command = sprintf(
            'pdftotext %s -enc UTF-8 %s %s',
            $mode,
            escapeshellarg($pdfPath),
            escapeshellarg($txtPath)
        );
        exec($command, $output, $code);

        $text = is_file($txtPath) ? trim((string) @file_get_contents($txtPath)) : '';
        @unlink($txtPath);

        if ($code === 0 && $text !== '') {
            return $text;
        }

        return null;
    }

    private function runMutool(string $pdfPath): ?string
    {
        if (!$this->hasCommand('mutool')) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'mutool');
        if ($tmp === false) {
            return null;
        }
        @unlink($tmp);
        $txtPath = $tmp . '.txt';

        $command = sprintf(
            'mutool draw -F txt -o %s %s',
            escapeshellarg($txtPath),
            escapeshellarg($pdfPath)
        );

        exec($command, $output, $code);
        $text = is_file($txtPath) ? trim((string) @file_get_contents($txtPath)) : '';
        @unlink($txtPath);

        if ($code === 0 && $text !== '') {
            return $text;
        }

        return null;
    }

    private function runOcrFallback(string $pdfPath): ?string
    {
        if (!$this->hasCommand('pdftoppm') || !$this->hasCommand('tesseract')) {
            return null;
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'ocr');
        if ($tmpBase === false) {
            return null;
        }

        $dir = dirname($tmpBase);
        $prefix = $dir . '/ocr_page';

        $command = sprintf(
            'pdftoppm -r 300 %s %s',
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        );
        exec($command);

        $ppmFiles = glob($prefix . '-*.ppm') ?: [];
        sort($ppmFiles, SORT_NATURAL);
        $ppmFiles = array_slice($ppmFiles, 0, 10);

        $chunks = [];
        foreach ($ppmFiles as $ppm) {
            $outBase = $ppm . '_ocr';
            $cmd = sprintf(
                'tesseract %s %s -l nld+eng --psm 6',
                escapeshellarg($ppm),
                escapeshellarg($outBase)
            );
            exec($cmd);
            $txt = $outBase . '.txt';
            if (is_file($txt)) {
                $chunks[] = trim((string) @file_get_contents($txt));
            }
            @unlink($txt);
            @unlink($ppm);
        }

        $text = trim(implode("\n\n", array_filter($chunks, fn ($chunk) => $chunk !== '')));

        return $text !== '' ? $text : null;
    }

    private function hasCommand(string $command): bool
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = proc_open(
            "{$which} " . escapeshellarg($command),
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            return false;
        }

        $output = stream_get_contents($pipes[1]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        return proc_close($process) === 0 && trim((string) $output) !== '';
    }

    private function fetchJson(string $url): array
    {
        $response = Http::timeout(60)->acceptJson()->get($url);
        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} voor {$url}");
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('Ongeldige JSON-respons.');
        }

        return $data;
    }

    private function buildUrl(string $path, array $params = []): string
    {
        $query = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[] = $key . '=' . rawurlencode((string) $value);
        }

        $suffix = $query ? '?' . implode('&', $query) : '';
        return $this->baseUrl . $path . $suffix;
    }

    private function buildZaakUrl(string $nummer): string
    {
        $nummer = $this->normalizeNummer($nummer);
        $selectZaak = 'Id,Nummer,Soort,Titel,Onderwerp';
        $selectBesluit = 'Id,BesluitSoort,BesluitTekst,Status,StemmingsSoort';
        $selectStemming = 'Soort,ActorNaam,ActorFractie,Fractie_Id,Persoon_Id,FractieGrootte';
        $filter = "Verwijderd eq false and Soort eq 'Motie' and Nummer eq '{$nummer}'";

        $expand = sprintf(
            'Besluit($filter=%s;$select=%s;$expand=Stemming($filter=%s;$select=%s))',
            'Verwijderd eq false',
            $selectBesluit,
            'Verwijderd eq false',
            $selectStemming
        );

        return $this->buildUrl('/Zaak', [
            '$filter' => $filter,
            '$select' => $selectZaak,
            '$expand' => $expand,
            '$top' => 1,
        ]);
    }

    private function buildZaakToDocumentUrl(string $zaakId): string
    {
        $selectDoc = 'Id,Soort,DocumentNummer,Titel,Onderwerp,Datum,Volgnummer,ContentType';
        $selectVersie = 'Id,Versienummer,Datum,Extensie,ExterneIdentifier';
        $selectPub = 'Identifier,Url,PublicatieDatum';

        $expand = sprintf(
            'DocumentVersie($select=%s;$orderby=%s;$top=1;$expand=DocumentPublicatie($select=%s))',
            $selectVersie,
            'Datum desc',
            $selectPub
        );

        return $this->buildUrl("/Zaak({$zaakId})/Document", [
            '$filter' => "Verwijderd eq false and Soort eq 'Motie'",
            '$select' => $selectDoc,
            '$orderby' => 'Datum desc',
            '$top' => 1,
            '$expand' => $expand,
        ]);
    }

    private function normalizeNummer(string $nummer): string
    {
        $nummer = str_replace(['’', '‘', '“', '”'], ["'", "'", '"', '"'], $nummer);
        return trim($nummer);
    }
}
