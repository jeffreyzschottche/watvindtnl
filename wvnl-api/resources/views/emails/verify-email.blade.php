@extends('emails.layout')

@section('title', 'Bevestig je WDNL-account')

@section('preheader')
Bevestig je e-mailadres om direct mee te doen aan Wat Denkt Nederland.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:15px 30px;background-color:#ff8e00;color:#ffffff;font-weight:700;border-radius:999px;text-decoration:none;font-size:16px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#6b768f;">Accountactivatie</p>
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.3;color:#002347;">Welkom bij WDNL, {{ $name }}!</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
        Nog één stap en je kunt direct deelnemen aan de discussies en peilingen van Wat Denkt Nederland.
        Bevestig je e-mailadres zodat we zeker weten dat jij het bent.
    </p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Klik op de knop hieronder om je account te activeren.</p>
    <p style="margin:0 0 24px;text-align:center;">
        <a href="{{ $verificationUrl }}" style="{{ $buttonStyle }}">E-mailadres bevestigen</a>
    </p>
    <div style="margin:0 0 24px;padding:16px 18px;border-radius:18px;background-color:#f5f7fb;color:#31405b;font-size:14px;line-height:1.5;">
        <strong style="display:block;margin-bottom:6px;color:#002347;">Waarom bevestigen?</strong>
        We beschermen zo jouw profiel en zorgen ervoor dat alleen jij toegang hebt tot je account.
    </div>
    <p style="margin:0 0 8px;font-size:14px;color:#6b768f;">Werkt de knop niet? Kopieer en plak de onderstaande link in je browser:</p>
    <p style="margin:0;padding:14px 16px;background:#f0f3fa;border-radius:16px;font-size:13px;word-break:break-all;">
        <a href="{{ $verificationUrl }}" style="color:#003f7d;text-decoration:none;">{{ $verificationUrl }}</a>
    </p>
@endsection
