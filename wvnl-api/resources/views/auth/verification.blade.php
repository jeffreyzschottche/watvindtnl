<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mailadres bevestigd</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light dark;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #1f7aec 0%, #0b1b3f 100%);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #fff;
            padding: 1.5rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px);
            border-radius: 24px;
            max-width: 540px;
            width: 100%;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 25px 60px rgba(9, 22, 52, 0.35);
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        p {
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 999px;
            font-weight: 600;
            background: rgba(15, 111, 255, 0.2);
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .status svg {
            width: 24px;
            height: 24px;
        }
        .muted {
            font-size: 0.9rem;
            opacity: 0.7;
        }
    </style>
    <script>
        setTimeout(function () {
            window.location.href = '/login';
        }, 2000);
    </script>
</head>
<body>
    <div class="card">
        <div class="status">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5" /></svg>
            @switch(request('status'))
                @case('already-verified')
                    <span>E-mailadres was al bevestigd</span>
                    @break
                @case('invalid')
                    <span>Verificatielink is verlopen of ongeldig</span>
                    @break
                @default
                    <span>E-mailadres bevestigd</span>
            @endswitch
        </div>
        @switch(request('status'))
            @case('already-verified')
                <h1>Goed nieuws!</h1>
                <p>Je e-mailadres was al bevestigd. We sturen je zo door naar de inlogpagina.</p>
                @break
            @case('invalid')
                <h1>Link ongeldig</h1>
                <p>Het lijkt erop dat deze verificatielink is verlopen of al gebruikt is. Vraag een nieuwe bevestigingsmail aan via de app om alsnog in te loggen.</p>
                @break
            @default
                <h1>Bedankt voor je bevestiging!</h1>
                <p>Je e-mailadres is succesvol bevestigd. Je kunt binnen enkele seconden inloggen op je WVNL-account.</p>
        @endswitch
        <p class="muted">Je wordt automatisch doorgestuurd naar /login.</p>
    </div>
</body>
</html>
