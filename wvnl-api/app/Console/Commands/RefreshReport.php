<?php

namespace App\Console\Commands;

use App\Jobs\GenerateVoteReport;
use App\Models\ReportOrder;
use App\Models\User;
use App\Services\Reports\ReportSettings;
use App\Services\Reports\VoteSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshReport extends Command
{
    protected $signature = 'reports:refresh
        {user : E-mailadres of gebruikers-ID}
        {--yes : Niet om bevestiging vragen}';

    protected $description = 'Maak voor een gebruiker die al een rapport kocht gratis een nieuw rapport met de huidige antwoorden en partijkoppelingen (eerdere rapporten blijven bestaan)';

    public function handle(VoteSnapshot $snapshots, ReportSettings $settings): int
    {
        $input = (string) $this->argument('user');
        $user = ctype_digit($input) ? User::find((int) $input) : User::where('email', $input)->first();
        if (! $user) {
            $this->error('Gebruiker niet gevonden.');

            return self::FAILURE;
        }
        $previous = ReportOrder::where('user_id', $user->id)->purchases()->where('status', 'ready')->latest('created_at')->first();
        if (! $previous) {
            $this->error('Deze gebruiker heeft nog geen gekocht rapport; een gratis vernieuwing is alleen bedoeld voor bestaande kopers.');

            return self::FAILURE;
        }

        $snapshot = $snapshots->build($user, true);
        $this->line(sprintf('Vorig rapport: %s (%d antwoorden, %d partijen). Nieuw: %d antwoorden, %d partijen%s.',
            $previous->created_at->format('d-m-Y H:i'), $previous->snapshot['total'] ?? 0, count($previous->snapshot['party_matches'] ?? []),
            $snapshot['total'], count($snapshot['party_matches']),
            $snapshot['party_matches'] ? ': '.collect($snapshot['party_matches'])->map(fn ($p) => "{$p['name']} {$p['matched']}/{$p['compared']}")->implode(', ') : ''));
        if (! $this->option('yes') && ! $this->confirm('Nieuw gratis rapport maken?', true)) {
            return self::SUCCESS;
        }

        $order = ReportOrder::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'request_key' => (string) Str::uuid(),
            'status' => 'paid', 'paid_at' => now(), 'snapshot' => $snapshot, 'snapshot_hash' => $snapshots->fingerprint($snapshot),
            'generation_settings' => $settings->current(), 'ai_consent' => $previous->ai_consent, 'amount_cents' => 0,
            'currency' => 'eur', 'consent_version' => $previous->consent_version]);
        $this->line('Rapport wordt gemaakt (met AI-toelichting kan dit een minuut duren)...');
        GenerateVoteReport::dispatchSync($order->id);

        $order->refresh();
        if ($order->status !== 'ready') {
            $this->error("Genereren mislukt ({$order->failure_code}). Order {$order->id} staat op {$order->status}.");

            return self::FAILURE;
        }
        $this->info("Klaar: nieuw rapport {$order->id} ({$order->narrative_source}). Je vindt het bij je rapporten op de site.");

        return self::SUCCESS;
    }
}
