<?php

namespace App\Console\Commands;

use App\Services\Reports\CompassProposals;
use App\Services\Reports\ReportSettings;
use Illuminate\Console\Command;

class ProposeCompassWeights extends Command
{
    protected $signature = 'compass:propose
        {--limit=100 : Maximaal aantal stellingen}
        {--batch=20 : Stellingen per modelaanvraag}
        {--max-eur=1 : Harde kostenlimiet voor deze run}
        {--model= : Ander toegestaan model dan in de rapportinstellingen}
        {--dry-run : Alleen tellen en kosten schatten}';

    protected $description = 'Laat het ingestelde LLM themagewichten voorstellen voor stellingen zonder gewichten. Voorstellen wachten op goedkeuring in zschotcms.';

    public function handle(CompassProposals $proposals, ReportSettings $settings): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $batch = max(1, min(50, (int) $this->option('batch')));
        $maxEur = max(0.01, min(25, (float) $this->option('max-eur')));
        $candidates = $proposals->candidates($limit);
        $this->info($candidates->count().' stellingen zonder gewichten of eerder voorstel geselecteerd.');
        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $s = $settings->current();
            $model = $this->option('model') ?: $s['model'];
            $rates = config('wdnl_reports.rates.'.$model) ?? ['input' => $s['input_usd_per_million'], 'output' => $s['output_usd_per_million']];
            $chars = 0;
            foreach ($candidates->chunk($batch) as $chunk) {
                $p = $proposals->prompt($chunk->values());
                $chars += strlen($p['instructions']) + strlen($p['input']) + strlen(json_encode($p['schema']));
            }
            $in = (int) ceil($chars / 3.5);
            $out = 110 * $candidates->count();
            $eur = ($in * $rates['input'] + $out * $rates['output']) / 1000000 * $s['usd_to_eur'];
            $this->line(sprintf('Model %s · circa %s input- en %s outputtokens · geschat €%.3f (zonder veiligheidsmarge).', $model, number_format($in, 0, ',', '.'), number_format($out, 0, ',', '.'), $eur));

            return self::SUCCESS;
        }
        $result = $proposals->run($limit, $batch, $maxEur, $this->option('model') ?: null, function ($t) {
            $this->line(sprintf('  batch %d · %d/%d voorstellen · €%.4f', $t['batches'], $t['proposals'], $t['statements'], $t['cost_eur']));
        });
        $this->info(sprintf('%d voorstellen opgeslagen, %d stellingen overgeslagen (ongeldige modeloutput), totaal €%.4f.', $result['proposals'], $result['skipped'], $result['cost_eur']));
        if ($result['stopped']) {
            $this->warn('Gestopt: '.$result['stopped'].'.');
        }
        $this->line('Beoordeel ze in zschotcms → WatDenktNederland content → Kompasvoorstellen.');

        return self::SUCCESS;
    }
}
