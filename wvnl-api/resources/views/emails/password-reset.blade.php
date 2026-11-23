@extends('emails.layout')

@section('title', 'Reset je wachtwoord')

@section('preheader')
Je hebt een verzoek gedaan om je wachtwoord opnieuw in te stellen.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:14px 28px;background-color:#0057b8;color:#ffffff;font-weight:700;border-radius:10px;text-decoration:none;font-size:16px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#0057b8;">Wachtwoord reset</p>
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.3;color:#001f4d;">We hebben je verzoek ontvangen, {{ $name }}.</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
        Klik op de knop hieronder om een nieuw wachtwoord te kiezen voor je WatDenktNederland-account.
        De link is 60 minuten geldig en kan maar één keer gebruikt worden.
    </p>
    <p style="margin:0 0 24px;text-align:center;">
        <a href="{{ $resetUrl }}" style="{{ $buttonStyle }}">Nieuw wachtwoord instellen</a>
    </p>
    <div style="margin:0 0 24px;padding:16px 18px;border-radius:12px;background-color:#fdf0f2;color:#5c1020;font-size:14px;line-height:1.5;">
        <strong style="display:block;margin-bottom:6px;color:#be0025;">Niet jij?</strong>
        Heb je dit niet zelf aangevraagd? Dan kun je deze e-mail negeren; je wachtwoord blijft ongewijzigd.
    </div>
    <p style="margin:0 0 8px;font-size:14px;color:#56657b;">Werkt de knop niet? Kopieer en plak de onderstaande link in je browser:</p>
    <p style="margin:0;padding:14px 16px;background:#f5f7fa;border-radius:12px;font-size:13px;word-break:break-all;">
        <a href="{{ $resetUrl }}" style="color:#0057b8;text-decoration:none;">{{ $resetUrl }}</a>
    </p>
@endsection
