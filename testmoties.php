#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Services\MotionImportService;
use Illuminate\Contracts\Console\Kernel;

$options = getopt('', ['limit::', 'keep-json', 'api-path::']);
$apiPath = resolveApiPath($options['api-path'] ?? null);

if (!is_dir($apiPath)) {
    fwrite(STDERR, "Kan de Laravel API-map niet vinden (gebruikt pad: {$apiPath})." . PHP_EOL);
    fwrite(STDERR, "Gebruik --api-path of stel MOTION_IMPORT_API_PATH/WVNL_API_PATH in." . PHP_EOL);
    exit(1);
}

require rtrim($apiPath, '/') . '/vendor/autoload.php';

$app = require rtrim($apiPath, '/') . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$defaultLimit = (int) config('motion-import.default_limit', 5);
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : $defaultLimit;
$keepJson = array_key_exists('keep-json', $options);

/** @var MotionImportService $service */
$service = $app->make(MotionImportService::class);

try {
    $result = $service->sync($limit, $keepJson);

    echo PHP_EOL;
    echo "✔ {$result['count']} moties geïmporteerd in de database." . PHP_EOL;
    echo "Laatste bronnummer: " . ($result['state']['last_processed_nummer'] ?? '-') . PHP_EOL;
    echo "Laatste bron-id: " . ($result['state']['last_processed_id'] ?? '-') . PHP_EOL;

    $issues = $result['issues'] ?? [];
    if (is_array($issues) && count($issues)) {
        echo PHP_EOL . "Nieuwe issues:" . PHP_EOL;
        foreach ($issues as $issue) {
            $title = $issue['title'] ?? 'Onbekende motie';
            $slug = $issue['slug'] ?? '-';
            echo "  • {$title} ({$slug})" . PHP_EOL;
        }
    }

    if ($keepJson) {
        echo PHP_EOL . "Let op: JSON-bestand is behouden (--keep-json gebruikt)." . PHP_EOL;
    }

    echo PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, PHP_EOL . '✖ ' . $e->getMessage() . PHP_EOL . PHP_EOL);
    exit(1);
}

/**
 * Bepaal het pad naar de Laravel API-code.
 */
function resolveApiPath(?string $cliValue): string
{
    if ($cliValue) {
        return rtrim($cliValue, '/');
    }

    $envKeys = ['MOTION_IMPORT_API_PATH', 'WVNL_API_PATH'];
    foreach ($envKeys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return rtrim($value, '/');
        }
    }

    foreach (possibleEnvFiles() as $envFile) {
        $value = readValueFromEnvFile($envFile, $envKeys);
        if ($value !== null) {
            return rtrim($value, '/');
        }
    }

    if (looksLikeLaravelRoot(__DIR__)) {
        return __DIR__;
    }

    return __DIR__ . '/wvnl-api';
}

/**
 * Vind mogelijke .env-bestanden in deze root.
 *
 * @return array<int,string>
 */
function possibleEnvFiles(): array
{
    $candidates = [
        __DIR__ . '/.env',
        __DIR__ . '/.env.local',
        __DIR__ . '/.env.production',
    ];

    return array_values(array_filter($candidates, fn ($file) => is_file($file) && is_readable($file)));
}

/**
 * Lees een waarde uit een .env-bestand voor de opgegeven sleutels.
 *
 * @param string[] $keys
 */
function readValueFromEnvFile(string $file, array $keys): ?string
{
    $contents = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($contents === false) {
        return null;
    }

    foreach ($contents as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (in_array($key, $keys, true) && $value !== '') {
            return $value;
        }
    }

    return null;
}

function looksLikeLaravelRoot(string $path): bool
{
    $path = rtrim($path, '/');
    return is_file($path . '/artisan')
        && is_dir($path . '/app')
        && is_dir($path . '/bootstrap')
        && is_file($path . '/bootstrap/app.php')
        && is_dir($path . '/vendor');
}
