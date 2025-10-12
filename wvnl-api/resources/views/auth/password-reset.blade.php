<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw wachtwoord instellen</title>
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
            background: linear-gradient(135deg, #0f1c3f, #1a73e8);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 1.5rem;
            color: #0b1221;
        }
        .container {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            box-shadow: 0 35px 90px rgba(7, 23, 54, 0.35);
            max-width: 460px;
            width: 100%;
            padding: 2.75rem 3rem;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: #091533;
        }
        p {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2d3858;
            line-height: 1.6;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        label {
            font-weight: 600;
            color: #0b1635;
            display: block;
            margin-bottom: 0.5rem;
        }
        input[type="password"] {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            border: 1px solid #c7d2f3;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.15);
        }
        button {
            padding: 0.95rem 1.25rem;
            border-radius: 14px;
            border: none;
            background: #1a73e8;
            color: #fff;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(26, 115, 232, 0.35);
        }
        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.12);
            color: #1c6f3e;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.12);
            color: #8a1f2a;
        }
        .helper {
            font-size: 0.9rem;
            color: #43537b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kies een nieuw wachtwoord</h1>
        <p>Maak een veilig wachtwoord dat je twee keer invult ter bevestiging. Daarna kun je direct weer inloggen.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/password-reset') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label for="password">Nieuw wachtwoord</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                <p class="helper">Minimaal 8 tekens en uniek voor WVNL.</p>
            </div>

            <div>
                <label for="password_confirmation">Herhaal wachtwoord</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit">Wachtwoord bevestigen</button>
        </form>
    </div>
</body>
</html>
