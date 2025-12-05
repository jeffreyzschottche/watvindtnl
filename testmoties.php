#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Services\MotionImportService;
use Illuminate\Contracts\Console\Kernel;

$apiPath = __DIR__ . '/wvnl-api';

if (!is_dir($apiPath)) {
    fwrite(STDERR, "Kan map 'wvnl-api' niet vinden. Voer dit script uit vanuit de projectroot.\n");
    exit(1);
}

require $apiPath . '/vendor/autoload.php';

$app = require $apiPath . '/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$options = getopt('', ['limit::', 'keep-json']);
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
