<?php
// Rebuild the clearly labelled public PDF example; no accounts, database writes or LLM calls.
$backend=dirname(__DIR__,3).'/wvnl-api';
require $backend.'/vendor/autoload.php';
$app=require $backend.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$entries=[];$metadata=[];
for($i=1;$i<=10;$i++){
    $entries[]=['id'=>$i,'slug'=>'voorbeeld-'.$i,'title'=>'Fictieve voorbeeldstelling','choice'=>$i<=8?'agree':'disagree'];
    $metadata[$i]=(object)['verified_at'=>'2026-11-08 12:00:00','source_date'=>'2026-11-08','motion_id'=>null,'compass_weights'=>json_encode(array_fill_keys(array_keys(App\Services\Reports\CompassAnalysis::DIMENSIONS),1))];
}
// Fix reference date so source-age checks do not change this design fixture over time.
Illuminate\Support\Carbon::setTestNow('2026-11-08 12:00:00');
$compass=app(App\Services\Reports\CompassAnalysis::class)->build($entries,$metadata);
$snapshot=['demo'=>true,'compass'=>$compass,'methodology_version'=>$compass['version'],'total'=>10,'counts'=>['agree'=>8,'disagree'=>2,'neutral'=>0],'entries'=>$entries,'party_matches'=>[],'generated_at'=>'2026-11-08T12:00:00+01:00'];
foreach(['A','B','C','D','E'] as $i=>$name)$snapshot['party_matches'][]=['name'=>'Fictieve voorbeeldpartij '.$name,'percentage'=>80-$i*10,'matched'=>8-$i,'compared'=>10];
$narrative=['summary'=>'Dit is een opmaakvoorbeeld met fictieve antwoorden. In je eigen rapport volgen het type, de themascores en partijovereenkomsten uit jouw vastgelegde stemmen en gecontroleerde inhoud.','reflection'=>'Dit voorbeeld laat de presentatie zien. De fictieve partijen en percentages zeggen niets over bestaande politieke partijen.'];
$pdf=app(App\Services\Reports\ReportPdf::class)->render($snapshot,$narrative);
file_put_contents(dirname(__DIR__,2).'/public/examples/WDNL-kompas-voorbeeld.pdf',$pdf);
echo "Labelled one-page compass PDF example generated; no real user data or external call.\n";
