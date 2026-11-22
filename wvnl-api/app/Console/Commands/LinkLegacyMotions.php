<?php

namespace App\Console\Commands;

use App\Models\Issue;
use App\Services\LegacyMotionLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkLegacyMotions extends Command
{
    protected $signature = 'motions:link-legacy
        {--limit=100 : Maximaal aantal stellingen in deze run}
        {--dry-run : Alleen tonen wat er zou gebeuren, niets opslaan}
        {--retry : Ook stellingen opnieuw proberen die eerder niet gekoppeld konden worden}
        {--id=* : Alleen deze stelling-ID(s)}';

    protected $description = 'Zoek voor oudere stellingen de officiële motie terug, laat AI motie en richting bevestigen en koppel de officiële partijstemmen';

    public function handle(LegacyMotionLinker $linker): int
    {
        $query = Issue::query()->orderBy('id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('wdnl_statement_metadata as m')->whereColumn('m.issue_id', 'issues.id')->whereNotNull('m.motion_id'));
        if ($ids = array_map('intval', (array) $this->option('id'))) {
            $query->whereIn('id', $ids);
        } elseif (! $this->option('retry')) {
            $query->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('wdnl_statement_metadata as m')->whereColumn('m.issue_id', 'issues.id')->whereNotNull('m.motion_match_checked_at'));
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
}
