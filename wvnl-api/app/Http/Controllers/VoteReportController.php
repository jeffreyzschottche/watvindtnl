<?php

namespace App\Http\Controllers;

use App\Models\ReportOrder;
use App\Services\Reports\ReportSettings;
use App\Services\Reports\StripeReports;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Exception\SignatureVerificationException;
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
        return ReportOrder::where('user_id', $request->user()->id)->where('id', $id)->firstOrFail();
    }

    private function serialize(ReportOrder $order, bool $detail = false): array
    {
        $result = $order->only(['id', 'status', 'amount_cents', 'currency']);
        $result += ['created_at' => $order->created_at?->toIso8601String(), 'ready_at' => $order->ready_at?->toIso8601String(),
            'vote_count' => $order->snapshot['total'], 'checkout_url' => $order->status === 'pending' ? $order->checkout_url : null,
            'download_available' => $order->status === 'ready'];
        if ($detail && $order->status === 'ready') {
            $result += ['snapshot' => $order->snapshot, 'narrative' => $order->narrative, 'narrative_source' => $order->narrative_source];
        }

        return $result;
    }

    public function index(Request $request): array
    {
        $snapshot = $this->snapshots->build($request->user());
        $previous = ReportOrder::where('user_id', $request->user()->id)->where('status', 'ready')->latest('created_at')->first();

        return ['compass_progress' => ['ready' => $snapshot['compass']['status'] === 'ready' && $snapshot['compass']['spectrum']['percentage'] !== null && ! $snapshot['compass']['spectrum']['provisional'], 'used_votes' => $snapshot['compass']['used_votes'], 'dimensions' => array_map(fn ($d) => ['name' => $d['left'], 'compared' => $d['compared'], 'minimum' => $d['minimum']], [...$snapshot['compass']['dimensions'], $snapshot['compass']['spectrum']])], 'product' => $this->product(), 'vote_count' => $snapshot['total'], 'comparison' => $this->snapshots->comparison($snapshot, $previous, $this->settings->current()['renew_after_votes']),
            'orders' => ReportOrder::where('user_id', $request->user()->id)->latest('created_at')->limit(30)->get(['id', 'status', 'amount_cents', 'currency', 'created_at', 'ready_at', 'snapshot', 'checkout_url'])->map(fn ($o) => $this->serialize($o))->all()];
    }

    public function show(Request $request, string $id): array
    {
        return $this->serialize($this->own($request, $id), true);
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
            abort_unless($snapshot['compass']['status'] === 'ready' && $snapshot['compass']['spectrum']['percentage'] !== null && ! $snapshot['compass']['spectrum']['provisional'], 422, 'Je kompas is nog in opbouw. Er zijn meer antwoorden op gecontroleerd gewogen stellingen nodig voordat je een volledig rapport kunt kopen.');
            $order = ReportOrder::firstOrCreate(['user_id' => $request->user()->id, 'request_key' => $data['request_key']],
                ['id' => (string) Str::uuid(), 'status' => 'pending', 'snapshot' => $snapshot, 'snapshot_hash' => $this->snapshots->fingerprint($snapshot), 'generation_settings' => $settings,
                    'ai_consent' => $data['ai_consent'], 'amount_cents' => 199, 'currency' => 'eur', 'consent_version' => 'report-v3']);
        }
        if ($order->status === 'pending') {
            try {
                $this->payments->checkout($order);
            } catch (Throwable $e) {
                // Stripe/network details stay out of public responses; the same request_key safely retries the same session.
                abort(503, 'De betaalpagina is tijdelijk niet bereikbaar. Probeer het opnieuw; je rapportaanvraag blijft bewaard.');
            }
        }

        return $this->serialize($order->fresh());
    }

    public function download(Request $request, string $id)
    {
        $order = $this->own($request, $id);
        abort_unless($order->status === 'ready' && $order->pdf, 409, 'Het rapport is nog niet beschikbaar.');

        return response(base64_decode($order->pdf, true), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="WDNL-stemrapport.pdf"', 'Cache-Control' => 'private, no-store']);
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
