<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $general = $siteSettings['general'] ?? [];
        $brandName = $general['brand_name'] ?? config('app.name', 'Ready To Pict');
    @endphp
    <title>{{ $title ?? $brandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,700|sora:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --rtp-bg: #f6f2e9;
            --rtp-paper: #fffdfa;
            --rtp-ink: #17140f;
            --rtp-muted: #6f675a;
            --rtp-primary: #c06b2a;
            --rtp-primary-soft: #f4deca;
            --rtp-accent: #2f6f61;
            --rtp-outline: #e8dccb;
            --rtp-shadow: 0 18px 35px -24px rgba(28, 17, 4, 0.5);
        }

        body {
            font-family: 'Sora', sans-serif;
            background: radial-gradient(circle at 10% 10%, #fff6ec 0%, transparent 38%), radial-gradient(circle at 90% 15%, #ecf8f3 0%, transparent 30%), var(--rtp-bg);
            color: var(--rtp-ink);
        }

        .display-font {
            font-family: 'Fraunces', serif;
            letter-spacing: -0.02em;
        }

        .card-soft {
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.94), rgba(255, 251, 246, 0.98));
            border: 1px solid var(--rtp-outline);
            box-shadow: var(--rtp-shadow);
        }

        .badge {
            border: 1px solid #ead9c3;
            background: #fff5e9;
            color: #8e4f1f;
        }
    </style>
</head>
<body class="min-h-screen">
    {{ $slot }}
</body>
</html>
