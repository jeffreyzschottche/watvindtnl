<?php

namespace App\Services\Reports;

use App\Jobs\GenerateVoteReport;
use App\Models\ReportOrder;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeReports
{
    public function available(): bool
    {
        return (bool) config('wdnl_reports.sales_enabled') && (bool) config('wdnl_reports.stripe_secret') && (bool) config('wdnl_reports.stripe_webhook_secret');
    }

    public function checkout(ReportOrder $order): string
    {
        abort_unless($this->available(), 503, 'Rapportbetalingen zijn nog niet geconfigureerd. Er wordt niets afgeschreven.');
        if ($order->checkout_url) {
            return $order->checkout_url;
        }
        abort_unless($order->status === 'pending', 409, 'Deze aanvraag heeft al een andere status.');
        $base = rtrim(config('wdnl_reports.web_url'), '/');
        $parts = parse_url($base);
        $local = in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true);
        abort_unless(($parts['scheme'] ?? '') === 'https' || (($parts['scheme'] ?? '') === 'http' && $local), 503, 'De betaalomgeving is nog niet ingesteld.');
        $stripe = new StripeClient(config('wdnl_reports.stripe_secret'));
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment', 'client_reference_id' => $order->id, 'metadata' => ['wdnl_report_order' => $order->id],
            'payment_method_types' => config('wdnl_reports.stripe_payment_methods') ?: ['ideal'], 'locale' => 'nl',
            'payment_intent_data' => ['metadata' => ['wdnl_report_order' => $order->id]],
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => 'eur', 'unit_amount' => 199, 'product_data' => ['name' => 'WDNL persoonlijk stemrapport', 'description' => 'Eén vast A4-rapport. Een nieuw rapport is een nieuwe aankoop.']]]],
            'success_url' => $base.'/kompas/rapport?order='.$order->id,
            'cancel_url' => $base.'/kompas/rapport?order='.$order->id.'&betaling=geannuleerd',
        ], ['idempotency_key' => 'wdnl-report-'.$order->id]);
        $url = (string) $session->url;
        abort_unless(parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === 'checkout.stripe.com', 502, 'Onverwachte betaalverwijzing.');
        $order->update(['stripe_session_id' => $session->id, 'checkout_url' => $url]);

        return $url;
    }

    public function webhook(string $raw, string $signature): void
    {
        abort_unless(config('wdnl_reports.stripe_webhook_secret') && config('wdnl_reports.stripe_secret'), 503);
        $event = Webhook::constructEvent($raw, $signature, config('wdnl_reports.stripe_webhook_secret'), 300);
        $live = str_starts_with((string) config('wdnl_reports.stripe_secret'), 'sk_live_');
        abort_unless((bool) $event->livemode === $live, 400, 'Verkeerde betaalomgeving.');
        DB::transaction(function () use ($event) {
            if (! DB::table('wdnl_report_payment_events')->insertOrIgnore(['id' => $event->id, 'type' => $event->type, 'created_at' => now()])) {
                return;
            }
            $session = $event->data->object;
            if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                $id = $session->metadata->wdnl_report_order ?? null;
                if (! $id) {
                    return;
                } // unrelated product webhook
                $order = ReportOrder::purchases()->where('id', $id)->lockForUpdate()->first();
                abort_unless($order, 400, 'Onbekende rapportorder.');
                abort_unless($session->mode === 'payment' && (int) $session->amount_total === $order->amount_cents && $session->currency === $order->currency && $session->client_reference_id === $order->id, 400, 'Betaalgegevens komen niet overeen.');
                abort_if($order->stripe_session_id && $order->stripe_session_id !== $session->id, 400, 'Verkeerde betaalsessie.');
                if ($session->payment_status !== 'paid' || $order->paid_at || $order->status === 'refunded') {
                    return;
                }
                $order->update(['status' => 'paid', 'paid_at' => now(), 'stripe_session_id' => $session->id, 'payment_intent_id' => (string) $session->payment_intent, 'checkout_url' => null]);
                GenerateVoteReport::dispatch($order->id)->afterCommit();
            } elseif ($event->type === 'charge.refunded' && ($session->refunded ?? false)) {
                $order = ReportOrder::where('payment_intent_id', (string) $session->payment_intent)->lockForUpdate()->first();
                if ($order) {
                    $order->update(['status' => 'refunded', 'pdf' => null, 'narrative' => null, 'checkout_url' => null]);
                }
            } elseif (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
                ReportOrder::where('stripe_session_id', $session->id)->whereNull('paid_at')->where('status', 'pending')->update(['status' => 'canceled', 'checkout_url' => null]);
            }
        });
    }
}
