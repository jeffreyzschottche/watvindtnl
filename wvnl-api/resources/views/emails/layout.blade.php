<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'WDNL')</title>
</head>
<body style="margin:0;padding:0;background-color:#e8edf4;font-family:'Inter',Arial,sans-serif;color:#081225;">
@hasSection('preheader')
    <div style="display:none!important;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        @yield('preheader')
    </div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#e8edf4;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 32px 70px rgba(0,35,71,0.18);">
                <tr>
                    <td style="background:linear-gradient(135deg,#002347,#003f7d);padding:26px 34px;text-align:left;">
                        <a href="https://www.wdnl.nl" style="font-size:26px;font-weight:800;letter-spacing:0.05em;color:#ffffff;text-decoration:none;">WDNL</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 34px 10px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 34px 32px;color:#31405b;font-size:14px;line-height:1.6;">
                        @hasSection('closing')
                            @yield('closing')
                        @else
                            <p style="margin:0 0 16px;">Vriendelijke groet,<br><strong>Team Wat Denkt Nederland</strong></p>
                            <p style="margin:0;font-size:13px;">Vragen? Mail ons via <a href="mailto:support@wdnl.nl" style="color:#ff8e00;text-decoration:none;">support@wdnl.nl</a>.</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#f5f7fb;padding:18px 34px;text-align:center;font-size:12px;color:#6b768f;">
                        © {{ date('Y') }} WDNL. Alle rechten voorbehouden.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
