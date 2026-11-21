<?php

namespace App\Console\Commands;

use App\Services\Reports\CompassWeightsFile;
use Illuminate\Console\Command;

class ExportCompassWeights extends Command
{
    protected $signature = 'compass:export';

    protected $description = 'Schrijf alle goedgekeurde kompasgewichten naar resources/content/compass-weights.json, zodat de seedknop in zschotcms ze overal neerzet';

    public function handle(CompassWeightsFile $file): int
    {
        $count = $file->write();
        $this->info($count.' goedgekeurde gewichten vastgelegd in '.CompassWeightsFile::path().'. Commit dit bestand en deploy; daarna zet "Basiswegingen seeden" ze neer.');

        return self::SUCCESS;
    }
}
