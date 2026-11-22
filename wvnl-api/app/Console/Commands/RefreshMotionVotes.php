<?php

namespace App\Console\Commands;

use App\Services\MotionVotes;
use Illuminate\Console\Command;

class RefreshMotionVotes extends Command
{
    protected $signature = 'motions:refresh-votes {--limit=200}';

    protected $description = 'Haal officiële partijstemmen op voor gekoppelde stellingen die ze nog missen (moties worden vaak later in stemming gebracht)';

    public function handle(MotionVotes $votes): int
    {
        $result = $votes->refreshLinked(max(1, (int) $this->option('limit')));
        $this->info(sprintf('%d gecontroleerd, %d bijgewerkt met stemmen, %d niet bereikbaar.', $result['checked'], $result['updated'], $result['failed']));

        return self::SUCCESS;
    }
}
