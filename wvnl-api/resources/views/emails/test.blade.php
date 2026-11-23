@extends('emails.layout')

@section('title', 'Testbericht')

@section('preheader')
Dit is een voorbeeldmail om de opmaak te controleren.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:14px 28px;background-color:#0057b8;color:#ffffff;font-weight:700;border-radius:10px;text-decoration:none;font-size:16px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#0057b8;">Systeemtest</p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.35;color:#001f4d;">Dit is een testmail van WatDenktNederland</h1>
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Gebruik dit bericht om te controleren of de huisstijl correct wordt weergegeven in je e-mailclient.
    </p>
    <p style="margin:0 0 26px;text-align:center;">
        <a href="{{ rtrim((string) config('app.front_url'), '/') }}" style="{{ $buttonStyle }}">Naar de site</a>
    </p>
@endsection
