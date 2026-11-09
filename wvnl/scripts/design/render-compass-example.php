<?php
// Public design fixture: fictional choices only. No database writes, accounts or model call.
$backend = dirname(__DIR__, 3).'/wvnl-api';
require $backend.'/vendor/autoload.php';
$app = require $backend.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$fixture = json_decode(file_get_contents(__DIR__.'/compass-example-v3.json'), true, flags: JSON_THROW_ON_ERROR);
$pdf = app(App\Services\Reports\ReportPdf::class)->render($fixture['snapshot'], $fixture['narrative']);
file_put_contents(dirname(__DIR__, 2).'/public/examples/WDNL-kompas-voorbeeld.pdf', $pdf);
echo "Labelled two-page A4 example generated from fictional fixture; no external request.\n";
