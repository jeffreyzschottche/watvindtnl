@component('mail::message')
# Wachtwoord resetten

Hoi {{ $name }},

We hebben een verzoek ontvangen om je wachtwoord voor WVNL opnieuw in te stellen. Klik op de knop hieronder om een nieuw wachtwoord te kiezen.

@component('mail::button', ['url' => $resetUrl])
Nieuw wachtwoord instellen
@endcomponent

Werkt de knop niet? Kopieer en plak deze link in je browser:
{{ $resetUrl }}

Als je dit verzoek niet hebt gedaan kun je deze e-mail gerust negeren.

Met vriendelijke groet,

Het WVNL-team
@endcomponent
