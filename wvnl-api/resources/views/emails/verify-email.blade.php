@component('mail::message')
# Welkom bij Wat Denkt Nederland, {{ $name }}!

Leuk dat je je hebt geregistreerd. Klik op de knop hieronder om je e-mailadres te bevestigen en toegang te krijgen tot je account.

@component('mail::button', ['url' => $verificationUrl])
E-mailadres bevestigen
@endcomponent

Werkt de knop niet? Kopieer en plak deze link in je browser:
{{ $verificationUrl }}

Bedankt voor je aanmelding!

Met vriendelijke groet,

Het WDNL-team
@endcomponent
