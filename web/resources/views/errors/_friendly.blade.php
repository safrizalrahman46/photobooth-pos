<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Terjadi Kendala' }} - Ready To Pict</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600,700|sora:400,500,600,700" rel="stylesheet" />
    <style>
        :root {
            --bg: #f6f2e9;
            --paper: #fffdfa;
            --ink: #17140f;
            --muted: #6f675a;
            --primary: #c06b2a;
            --outline: #e8dccb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(circle at 20% 10%, #fff6ec 0%, transparent 35%), radial-gradient(circle at 90% 20%, #ecf8f3 0%, transparent 28%), var(--bg);
            color: var(--ink);
            font-family: Sora, system-ui, sans-serif;
        }

        main {
            width: min(100%, 560px);
            padding: 34px;
            border: 1px solid var(--outline);
            border-radius: 28px;
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.94), rgba(255, 251, 246, 0.98));
            box-shadow: 0 24px 55px -32px rgba(28, 17, 4, 0.55);
            text-align: center;
        }

        .code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 74px;
            height: 42px;
            border-radius: 999px;
            background: #fff5e9;
            color: #8e4f1f;
            font-weight: 700;
        }

        h1 {
            margin: 18px 0 10px;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(30px, 8vw, 44px);
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        p {
            margin: 0 auto 24px;
            max-width: 440px;
            color: var(--muted);
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
        }

        .primary {
            background: var(--primary);
            color: white;
        }

        .secondary {
            border: 1px solid var(--outline);
            color: var(--ink);
            background: white;
        }
    </style>
</head>
<body>
    <main>
        <span class="code">{{ $code ?? 'Error' }}</span>
        <h1>{{ $heading ?? 'Terjadi kendala.' }}</h1>
        <p>{{ $message ?? 'Halaman belum bisa diproses saat ini. Silakan coba lagi beberapa saat lagi.' }}</p>
        <div class="actions">
            <a class="primary" href="{{ route('landing') }}">Ke Beranda</a>
            <a class="secondary" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('landing') }}">Kembali</a>
        </div>
    </main>
</body>
</html>
