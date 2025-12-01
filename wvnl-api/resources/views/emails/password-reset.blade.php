@extends('emails.layout')

@section('title', 'Reset je WDNL-wachtwoord')

@section('preheader')
Je hebt een verzoek gedaan om je WDNL-wachtwoord opnieuw in te stellen.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:15px 30px;background-color:#ff8e00;color:#ffffff;font-weight:700;border-radius:999px;text-decoration:none;font-size:16px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#6b768f;">Wachtwoord reset</p>
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.3;color:#002347;">We hebben je verzoek ontvangen, {{ $name }}.</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
        Klik op de knop hieronder om een nieuw wachtwoord te kiezen voor je WDNL-account.
        De link is 60 minuten geldig en kan maar één keer gebruikt worden.
    </p>
    <p style="margin:0 0 24px;text-align:center;">
        <a href="{{ $resetUrl }}" style="{{ $buttonStyle }}">Nieuw wachtwoord instellen</a>
    </p>
    <div style="margin:0 0 24px;padding:16px 18px;border-radius:18px;background-color:#fff4ec;color:#66390d;font-size:14px;line-height:1.5;">
        <strong style="display:block;margin-bottom:6px;color:#c24f00;">Niet jij?</strong>
        Negeer deze e-mail en wijzig meteen je wachtwoord wanneer je verdachte activiteit ziet.
    </div>
    <p style="margin:0 0 8px;font-size:14px;color:#6b768f;">Werkt de knop niet? Kopieer en plak de onderstaande link in je browser:</p>
    <p style="margin:0;padding:14px 16px;background:#f0f3fa;border-radius:16px;font-size:13px;word-break:break-all;">
        <a href="{{ $resetUrl }}" style="color:#003f7d;text-decoration:none;">{{ $resetUrl }}</a>
    </p>
@endsection
