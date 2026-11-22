<?php

namespace App\Http\Controllers;

use App\Models\ReportOrder;
use App\Services\Reports\CompassAnalysis;
use App\Services\Reports\DevelopmentReport;
use App\Services\Reports\ReportPdf;
use App\Services\Reports\ReportSettings;
use App\Services\Reports\StripeReports;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class VoteReportController extends Controller
{
    public function __construct(private ReportSettings $settings, private VoteSnapshot $snapshots, private StripeReports $payments) {}

    public function product(): array
    {
        $s = $this->settings->current();

        return ['price_cents' => 199, 'currency' => 'eur', 'purchase_type' => 'one_report_snapshot', 'minimum_votes' => $s['minimum_votes'],
            'checkout_available' => $this->payments->available(), 'ai_available' => (bool) ($s['ai_enabled'] && config('wdnl_reports.'.$s['provider'].'_key')),
            'ai_provider' => $s['provider'], 'methodology_version' => VoteSnapshot::VERSION, 'native_purchase_available' => false];
    }

    private function own(Request $request, string $id): ReportOrder
    {
        return ReportOrder::purchases()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    /** Light goal-gradient data for the app: how far the user's answers are from a complete compass. */
    public function progress(Request $request): array
    {
        $snapshot = $this->snapshots->build($request->user());
        $compass = $snapshot['compass'];
        $themes = array_map(fn ($d) => ['id' => $d['id'], 'left' => $d['left'], 'right' => $d['right'], 'compared' => $d['compared'], 'minimum' => $d['minimum'], 'done' => min($d['compared'], $d['minimum'])],
            [...$compass['dimensions'], $compass['spectrum']]);
        $done = array_sum(array_column($themes, 'done'));
        $target = array_sum(array_column($themes, 'minimum'));
        $open = array_values(array_filter($themes, fn ($t) => $t['done'] < $t['minimum']));
        usort($open, fn ($a, $b) => $a['done'] <=> $b['done']);

        $remainingVotes = max(0, $this->settings->current()['minimum_votes'] - $snapshot['total']);

        return ['done' => $done, 'target' => $target, 'needed' => $target - $done, 'used_votes' => $compass['used_votes'], 'status' => $compass['status'],
            'vote_count' => $snapshot['total'], 'remaining_votes' => $remainingVotes,
            'remaining_weighted_votes' => max(0, CompassAnalysis::MINIMUM_USED_VOTES - $compass['used_votes']),
            'report_ready' => $remainingVotes === 0 && $this->hasReportCoverage($compass), 'checkout_available' => $this->payments->available(),
            'type' => $compass['type'] ? ['name' => $compass['type']['name'], 'provisional' => $compass['type']['provisional']] : null,
            'next_theme' => $open ? $open[0]['left'].' ↔ '.$open[0]['right'] : null, 'themes' => $themes];
    }

    public function index(Request $request, DevelopmentReport $development): array
    {
        $user = $request->user();
        $snapshot = $this->snapshots->build($user);
        $orders = ReportOrder::where('user_id', $user->id);
        $previous = (clone $orders)->purchases()->where('status', 'ready')->latest('created_at')->first();
        if (! $development->allowed($user)) {
            $orders->purchases();
        }
        $page = $orders->latest('created_at')->orderByDesc('id')->simplePaginate(30, ['id', 'status', 'is_development', 'amount_cents', 'currency', 'created_at', 'ready_at', 'snapshot', 'checkout_url']);

        return ['compass_progress' => ['ready' => $this->hasReportCoverage($snapshot['compass']), 'used_votes' => $snapshot['compass']['used_votes'], 'dimensions' => array_map(fn ($d) => ['name' => $d['left'], 'compared' => $d['compared'], 'minimum' => $d['minimum']], [...$snapshot['compass']['dimensions'], $snapshot['compass']['spectrum']])], 'product' => $this->product(), 'vote_count' => $snapshot['total'], 'comparison' => $this->snapshots->comparison($snapshot, $previous, $this->settings->current()['renew_after_votes']),
            'orders' => $page->getCollection()->map(fn (ReportOrder $order) => $order->reportData())->all(),
            'orders_next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null];
    }

    public function show(Request $request, string $id): array
    {
        return $this->own($request, $id)->reportData(true);
    }

    public function store(Request $request): array
    {
        $data = $request->validate(['request_key' => ['required', 'uuid'], 'political_consent' => ['required', 'accepted'], 'ai_consent' => ['required', 'boolean'], 'platform' => ['required', 'in:web,ios,android']]);
        abort_unless($data['platform'] === 'web', 409, 'In-app aankopen zijn nog niet ingesteld. Er wordt niets afgeschreven.');
        abort_unless($this->payments->available(), 503, 'Rapportbetalingen zijn nog niet beschikbaar. Er wordt niets afgeschreven.');
        $order = ReportOrder::where('user_id', $request->user()->id)->where('request_key', $data['request_key'])->first();
        if (! $order) {
            $snapshot = $this->snapshots->build($request->user(), true);
            $settings = $this->settings->current();
            abort_if($snapshot['total'] < $settings['minimum_votes'], 422, 'Beantwoord eerst minimaal '.$settings['minimum_votes'].' stellingen.');
            abort_unless($this->hasReportCoverage($snapshot['compass']), 422, 'Je kompas is nog in opbouw. Er zijn meer antwoorden op gecontroleerd gewogen stellingen nodig voordat je een volledig rapport kunt kopen.');
            $order = ReportOrder::firstOrCreate(['user_id' => $request->user()->id, 'request_key' => $data['request_key']],
                ['id' => (string) Str::uuid(), 'status' => 'pending', 'snapshot' => $snapshot, 'snapshot_hash' => $this->snapshots->fingerprint($snapshot), 'generation_settings' => $settings,
                    'ai_consent' => $data['ai_consent'], 'amount_cents' => 199, 'currency' => 'eur', 'consent_version' => 'report-v3']);
        }
        abort_if($order->is_development, 409, 'Deze aanvraag hoort bij een devrapport.');
        if ($order->status === 'pending') {
            try {
                DB::transaction(fn () => $this->payments->checkout(ReportOrder::lockForUpdate()->findOrFail($order->id)));
            } catch (Throwable $e) {
                // Stripe/network details stay out of public responses (but are logged); the same request_key safely retries the same session.
                report($e);
                abort(503, 'De betaalpagina is tijdelijk niet bereikbaar. Probeer het opnieuw; je rapportaanvraag blijft bewaard.');
            }
        }

        return $order->fresh()->reportData();
    }

    private function hasReportCoverage(array $compass): bool
    {
        return $compass['status'] === 'ready' && $compass['spectrum']['percentage'] !== null && ! $compass['spectrum']['provisional'];
    }

    public function download(Request $request, string $id, ReportPdf $pdf)
    {
        $order = $this->own($request, $id);
        abort_unless($order->status === 'ready', 409, 'Het rapport is nog niet beschikbaar.');

        try {
            return $pdf->download($order->snapshot, $order->narrative, 'WatDenktNederland-stemrapport.pdf', $order->pdf);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e); // e.g. a layout that no longer fits one A4
            abort(503, 'De PDF kon nu niet worden gemaakt. Je rapport blijft bewaard; probeer het later opnieuw.');
        }
    }

    public function webhook(Request $request)
    {
        abort_if(strlen($request->getContent()) > 262144, 413);
        try {
            $this->payments->webhook($request->getContent(), (string) $request->header('Stripe-Signature'));
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            abort(400, 'Ongeldige webhook.');
        }

        return response()->json(['received' => true]);
    }
}
