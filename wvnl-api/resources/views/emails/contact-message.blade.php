@component('mail::message')
# Nieuw bericht via het contactformulier

Je hebt een nieuw bericht ontvangen via het contactformulier op de website.

@component('mail::panel')
**Naam:** {{ $name }}

**E-mail:** {{ $email }}
@endcomponent

{{ $content }}

Met vriendelijke groet,<br>
Het Wat Denkt Nederland-team
@endcomponent
