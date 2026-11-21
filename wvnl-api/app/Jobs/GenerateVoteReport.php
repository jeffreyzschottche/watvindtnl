<?php

namespace App\Jobs;

use App\Models\ReportOrder;
use App\Services\Reports\ReportWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateVoteReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 150;

    public bool $failOnTimeout = true;

    public function __construct(public string $orderId)
    {
        $this->onQueue('reports');
    }

    public function handle(ReportWriter $writer): void
    {
        $claimed = ReportOrder::where('id', $this->orderId)->where('status', 'paid')->update(['status' => 'generating', 'updated_at' => now()]);
        if (! $claimed) {
            return;
        }
        $order = ReportOrder::findOrFail($this->orderId);
        try {
            $result = $writer->write($order);
            DB::transaction(function () use ($result) {
                $current = ReportOrder::where('id', $this->orderId)->lockForUpdate()->firstOrFail();
                if ($current->status !== 'generating') {
                    return;
                } // refunds win against an in-flight worker
                $current->update(['narrative' => $result['text'], 'narrative_source' => $result['source'], 'status' => 'ready', 'ready_at' => now(), 'failure_code' => null]);
            });
        } catch (Throwable) {
            ReportOrder::where('id', $this->orderId)->where('status', 'generating')->update(['status' => 'failed', 'failure_code' => 'generation_failed']);
        }
    }

    public function failed(?Throwable $e): void
    {
        ReportOrder::where('id', $this->orderId)->whereIn('status', ['paid', 'generating'])->update(['status' => 'failed', 'failure_code' => 'worker_failed']);
    }
}
