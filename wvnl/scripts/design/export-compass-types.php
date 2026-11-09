<?php
// PHP is the source of truth. Export presentation data for SVG generation; no DB, secrets or model call.
require dirname(__DIR__, 3).'/wvnl-api/app/Services/Reports/CompassTypes.php';
$types = [];
foreach (App\Services\Reports\CompassTypes::ALL as $id => $type) $types[] = ['id'=>$id] + $type;
file_put_contents(__DIR__.'/compass-types-v4.json', json_encode($types, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
echo "Sixteen presentation types exported from the server catalogue.\n";
