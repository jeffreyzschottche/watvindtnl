@extends('emails.layout')

@section('title', 'WDNL testbericht')

@section('preheader')
Dit is een voorbeeldmail om de WDNL-opmaak te controleren.
@endsection

@php
    $buttonStyle = 'display:inline-block;padding:14px 26px;background-color:#ff8e00;color:#ffffff;font-weight:700;border-radius:999px;text-decoration:none;font-size:15px;';
@endphp

@section('content')
    <p style="margin:0 0 14px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#6b768f;">Systeemtest</p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.35;color:#002347;">Dit is een WDNL-testmail</h1>
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Gebruik dit bericht om te controleren of de WDNL-stijl correct wordt weergegeven in je e-mailclient.
    </p>
    <p style="margin:0 0 26px;text-align:center;">
        <a href="https://www.wdnl.nl" style="{{ $buttonStyle }}">Bezoek WDNL</a>
    </p>
@endsection
