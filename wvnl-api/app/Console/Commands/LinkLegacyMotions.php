<?php

namespace App\Console\Commands;

use App\Models\Issue;
use App\Services\LegacyMotionLinker;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class LinkLegacyMotions extends Command
{
    protected $signature = 'motions:link-legacy
        {--limit=100 : Maximaal aantal stellingen in deze run}
        {--dry-run : Alleen tonen wat er zou gebeuren, niets opslaan}
        {--retry : Ook stellingen opnieuw proberen die eerder niet gekoppeld konden worden}
        {--id=* : Alleen deze stelling-ID(s)}
        {--parallel=1 : Aantal processen tegelijk (bijv. 6)}
        {--shard= : Intern: alleen deel i van n, als i/n}';

    protected $description = 'Zoek voor oudere stellingen de officiële motie terug, laat AI motie en richting bevestigen en koppel de officiële partijstemmen';

    public function handle(LegacyMotionLinker $linker): int
    {
        $parallel = max(1, (int) $this->option('parallel'));
        if ($parallel > 1 && ! $this->option('shard') && ! $this->option('id')) {
            return $this->runParallel($parallel);
        }

        $query = Issue::query()->orderBy('id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('wdnl_statement_metadata as m')->whereColumn('m.issue_id', 'issues.id')->whereNotNull('m.motion_id'));
        if ($ids = array_map('intval', (array) $this->option('id'))) {
            $query->whereIn('id', $ids);
        } elseif (! $this->option('retry')) {
            $query->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('wdnl_statement_metadata as m')->whereColumn('m.issue_id', 'issues.id')->whereNotNull('m.motion_match_checked_at'));
        }
        if (preg_match('#^(\d+)/(\d+)$#', (string) $this->option('shard'), $m) && $m[2] > 1) {
            $query->whereRaw('issues.id % ? = ?', [(int) $m[2], ((int) $m[1] - 1) % (int) $m[2]]);
        }
        $total = (clone $query)->count();
        $issues = $query->limit(max(1, (int) $this->option('limit')))->get(['id', 'slug', 'title', 'description', 'more_info', 'party_stances']);
        $this->info(sprintf('%d stellingen te controleren, deze run: %d%s.', $total, $issues->count(), $this->option('dry-run') ? ' (dry-run, er wordt niets opgeslagen)' : ''));

        $counts = [];
        $bar = $this->output->createProgressBar($issues->count());
        foreach ($issues as $issue) {
            $result = $linker->link($issue, (bool) $this->option('dry-run'));
            $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;
            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line(sprintf('#%d %s → %s %s %s', $issue->id, mb_substr($issue->title, 0, 70), $result['status'], $result['number'] ?? '', isset($result['votes']) ? ($result['votes'] ? '(met stemmen)' : '(geen stemmen)') : ''));
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $labels = ['linked' => 'gekoppeld', 'no_match' => 'geen passende motie', 'not_confirmed' => 'motie of richting niet zeker', 'no_candidates' => 'niets gevonden',
            'motion_already_linked' => 'motie al gekoppeld aan andere stelling', 'error' => 'fout (volgende run opnieuw)'];
        $this->table(['Resultaat', 'Aantal'], collect($counts)->map(fn ($n, $s) => [$labels[$s] ?? $s, $n])->values()->all());

        return self::SUCCESS;
    }

    /** Most time is spent waiting on the Tweede Kamer API and OpenAI, so separate processes each take a slice of the statements. */
    private function runParallel(int $parallel): int
    {
        $this->info("Start {$parallel} processen tegelijk; elk neemt een deel van de stellingen.");
        $base = [PHP_BINARY, base_path('artisan'), 'motions:link-legacy', '--limit='.(int) ceil(max(1, (int) $this->option('limit')) / $parallel)];
        foreach (['dry-run', 'retry'] as $flag) {
            if ($this->option($flag)) {
                $base[] = '--'.$flag;
            }
        }
        $verbose = $this->output->isVerbose();
        $pool = Process::pool(function (Pool $pool) use ($parallel, $base, $verbose) {
            for ($i = 1; $i <= $parallel; $i++) {
                $pool->as((string) $i)->forever()->command([...$base, "--shard={$i}/{$parallel}", ...($verbose ? ['-v'] : [])]);
            }
        })->start(function (string $type, string $output, string $key) {
            foreach (preg_split('/\R/', trim($output)) as $line) {
                if (trim($line) !== '' && ! str_contains($line, '░') && ! str_contains($line, '▓')) {
                    $this->line("[{$key}] {$line}");
                }
            }
        });
        $failed = $pool->wait()->collect()->filter(fn ($result) => ! $result->successful())->count();

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
