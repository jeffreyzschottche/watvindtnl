@extends('emails.layout')

@section('title', 'Bevestig je account')

@section('preheader')
Bevestig je e-mailadres om mee te doen met WatDenktNederland.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:14px 28px;background-color:#0057b8;color:#ffffff;font-weight:700;border-radius:10px;text-decoration:none;font-size:16px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#0057b8;">Accountactivatie</p>
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.3;color:#001f4d;">Welkom bij WatDenktNederland, {{ $name }}!</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
        Nog één stap en je kunt meestemmen op stellingen en je eigen politieke kompas opbouwen.
        Bevestig je e-mailadres zodat we zeker weten dat jij het bent.
    </p>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Klik op de knop hieronder om je account te activeren.</p>
    <p style="margin:0 0 24px;text-align:center;">
        <a href="{{ $verificationUrl }}" style="{{ $buttonStyle }}">E-mailadres bevestigen</a>
    </p>
    <div style="margin:0 0 24px;padding:16px 18px;border-radius:12px;background-color:#f5f7fa;color:#33425a;font-size:14px;line-height:1.5;">
        <strong style="display:block;margin-bottom:6px;color:#001f4d;">Waarom bevestigen?</strong>
        We beschermen zo jouw profiel en zorgen ervoor dat alleen jij toegang hebt tot je account.
    </div>
    <p style="margin:0 0 8px;font-size:14px;color:#56657b;">Werkt de knop niet? Kopieer en plak de onderstaande link in je browser:</p>
    <p style="margin:0;padding:14px 16px;background:#f5f7fa;border-radius:12px;font-size:13px;word-break:break-all;">
        <a href="{{ $verificationUrl }}" style="color:#0057b8;text-decoration:none;">{{ $verificationUrl }}</a>
    </p>
@endsection
