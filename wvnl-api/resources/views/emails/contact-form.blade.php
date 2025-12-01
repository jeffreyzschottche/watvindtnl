@extends('emails.layout')

@section('title', 'Nieuw bericht via het contactformulier')

@section('preheader')
{{ $name }} liet een bericht achter via het WDNL-contactformulier.
@endsection

@section('content')
    <p style="margin:0 0 14px;font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#6b768f;">Contactformulier</p>
    <h1 style="margin:0 0 18px;font-size:26px;line-height:1.3;color:#002347;">Nieuw bericht van {{ $name }}</h1>
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">De volgende gegevens zijn meegestuurd vanaf het formulier.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;border-collapse:collapse;">
        <tr>
            <td style="padding:12px 16px;background:#f5f7fb;border-radius:16px 16px 0 0;color:#6b768f;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;">Naam</td>
        </tr>
        <tr>
            <td style="padding:14px 16px;border-bottom:1px solid #e3e8f2;font-size:16px;font-weight:600;">{{ $name }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f5f7fb;color:#6b768f;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;">E-mailadres</td>
        </tr>
        <tr>
            <td style="padding:14px 16px;border-bottom:1px solid #e3e8f2;font-size:16px;font-weight:600;">
                <a href="mailto:{{ $email }}" style="color:#003f7d;text-decoration:none;">{{ $email }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background:#f5f7fb;color:#6b768f;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;border-radius:0 0 16px 16px;">Bericht</td>
        </tr>
    </table>
    <div style="padding:18px 20px;background:#fff4ec;border-radius:18px;font-size:15px;line-height:1.7;color:#2c1a04;">
        {!! nl2br(e($messageBody)) !!}
    </div>
@endsection

@section('closing')
    <p style="margin:0;font-size:13px;color:#6b768f;">
        Dit bericht is automatisch doorgestuurd vanuit het formulier op wdnl.nl.
    </p>
@endsection
