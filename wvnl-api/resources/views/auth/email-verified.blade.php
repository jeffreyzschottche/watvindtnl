<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mail verificatie | Wat vindt NL</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(145deg, #f9fafb 0%, #dbeafe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #0f172a;
        }
        .card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 1.5rem;
            padding: 2.5rem;
            max-width: 32rem;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.15);
        }
        h1 {
            font-size: clamp(1.75rem, 5vw, 2.25rem);
            margin-bottom: 0.5rem;
        }
        p {
            line-height: 1.6;
            margin: 0.5rem 0 0;
        }
        .highlight {
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
        }
        .cta {
            display: inline-flex;
            margin-top: 2rem;
            padding: 0.85rem 1.4rem;
            border-radius: 999px;
            background: #1d4ed8;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .cta:hover {
            background: #1e40af;
        }
        .muted {
            color: #475569;
            font-size: 0.95rem;
            margin-top: 0.75rem;
        }
    </style>
</head>
<body>
    @php($loginBase = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') . '/login')
    @php($loginUrl = $loginBase . '?verify=1')

    <main class="card">
        <h1>
            {{ $alreadyVerified ? 'Je e-mailadres is al bevestigd' : 'Bedankt voor het bevestigen!' }}
        </h1>
        <p>
            {{ $userName ? $userName . ',' : '' }}
        </p>
        <p>
            @if ($alreadyVerified)
                Je had je e-mailadres al bevestigd. Je kunt nu inloggen in de app.
            @else
                Je e-mailadres is succesvol geverifieerd. Je kunt nu inloggen bij Wat vindt NL.
            @endif
        </p>
        <div class="highlight">
            <p>
                Open de app of ga naar de website en log in met je e-mailadres. Je hebt nu toegang tot alle functies.
            </p>
        </div>
        <a class="cta" href="{{ $loginUrl }}">
            Ga naar de loginpagina
        </a>
        <p class="muted">
            Werkt de knop niet? Kopieer dan deze link naar je browser:
            <br>
            <strong>{{ $loginUrl }}</strong>
        </p>
    </main>
</body>
</html>
