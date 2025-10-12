<x-mail::message>
# Nieuw bericht via het contactformulier

**Naam:** {{ $name }}

**E-mailadres:** {{ $email }}

**Bericht:**

{{ $messageBody }}

Groeten,<br>
{{ config('app.name') }}
</x-mail::message>
