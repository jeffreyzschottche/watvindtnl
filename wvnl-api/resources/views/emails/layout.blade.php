@php
    $site = rtrim((string) config('app.front_url'), '/');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'WatDenktNederland')</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:Manrope,'Segoe UI',Arial,sans-serif;color:#001f4d;">
@hasSection('preheader')
    <div style="display:none!important;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        @yield('preheader')
    </div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f5f7fa;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #dce3ee;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="padding:26px 32px 22px;text-align:left;">
                        <a href="{{ $site }}" style="text-decoration:none;">
                            <img src="{{ $site }}/brand/wdnl-logo.png" width="230" height="46" alt="WatDenktNederland" style="display:block;width:230px;height:46px;border:0;font-size:20px;font-weight:800;color:#001f4d;">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:0;line-height:0;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr><td height="3" style="height:3px;background-color:#e1062e;"></td></tr>
                            <tr><td height="3" style="height:3px;background-color:#ffffff;"></td></tr>
                            <tr><td height="3" style="height:3px;background-color:#0057b8;"></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 32px 8px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 32px 30px;color:#56657b;font-size:14px;line-height:1.6;">
                        @hasSection('closing')
                            @yield('closing')
                        @else
                            <p style="margin:0 0 14px;">Vriendelijke groet,<br><strong style="color:#001f4d;">Team WatDenktNederland</strong></p>
                            <p style="margin:0;font-size:13px;">Vragen? Neem <a href="{{ $site }}/contact" style="color:#0057b8;text-decoration:underline;">contact met ons op</a>.</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#f5f7fa;border-top:1px solid #dce3ee;padding:16px 32px;text-align:center;font-size:12px;color:#56657b;">
                        © {{ date('Y') }} WatDenktNederland · <a href="{{ $site }}" style="color:#56657b;text-decoration:none;">{{ preg_replace('#^https?://(www\.)?#', '', $site) }}</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
