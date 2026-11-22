<?php

namespace App\Console\Commands;

use App\Services\CmsMotionStatements;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DraftMotionStatements extends Command
{
    protected $signature = 'motions:draft {--limit=10 : Aantal nieuwe moties (max 10)}';

    protected $description = 'Haal nieuwe moties op waarover gestemd is en zet per motie een AI-stellingconcept klaar in ZschotCMS';

    public function handle(CmsMotionStatements $statements): int
    {
        $limit = min(10, max(1, (int) $this->option('limit')));
        try {
            $motions = $statements->prepare($limit, null);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }
        $this->info(count($motions).' nieuwe moties gevonden.');

        $created = 0;
        foreach ($motions as $motion) {
            try {
                $result = $statements->generate($motion['id'], null);
                $created += (int) $result['created'];
                $this->line(sprintf('%s → %s', $motion['number'], $result['created'] ? 'concept klaar' : 'al in gebruik, overgeslagen'));
            } catch (ValidationException $e) {
                $this->warn(sprintf('%s → mislukt: %s', $motion['number'], collect($e->errors())->flatten()->implode(' ')));
            } catch (HttpException $e) {
                $this->warn(sprintf('%s → mislukt: %s', $motion['number'], $e->getMessage()));
            }
        }
        $this->info("{$created} concepten klaargezet voor review in ZschotCMS.");

        return self::SUCCESS;
    }
}
