<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · {{ data_get($settings ?? null, 'store_name') ?: config('app.name', 'Akhi Telecom') }}</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --nexa-bg: #06070c;
            --nexa-muted: #94a3b8;
            --nexa-soft: #64748b;
        }
        .nexa-login {
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
            min-height: 100vh;
            background: var(--nexa-bg);
            color: #fff;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        .nexa-login * { box-sizing: border-box; }
        .nexa-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.92fr);
        }

        /* Left poster panel */
        .nexa-left {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            background: #06070c;
        }
        .nexa-left-poster {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .nexa-left-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: 70% center;
            display: block;
            user-select: none;
            pointer-events: none;
            image-rendering: -webkit-optimize-contrast;
        }
        .nexa-left-scrim {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg,
                    rgba(6, 7, 12, 0.94) 0%,
                    rgba(6, 7, 12, 0.82) 28%,
                    rgba(6, 7, 12, 0.45) 55%,
                    rgba(6, 7, 12, 0.18) 78%,
                    rgba(6, 7, 12, 0.35) 100%),
                linear-gradient(180deg,
                    rgba(6, 7, 12, 0.55) 0%,
                    transparent 22%,
                    transparent 78%,
                    rgba(6, 7, 12, 0.72) 100%);
            pointer-events: none;
        }
        .nexa-left-content {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px 44px 28px;
        }
        .nexa-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nexa-brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: #000;
            border: 1px solid rgba(148, 163, 184, .25);
            box-shadow: 0 0 28px rgba(37, 99, 235, .28);
        }
        .nexa-brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .nexa-brand-mark svg { width: 26px; height: 26px; }
        .nexa-brand-logo {
            height: 52px;
            width: auto;
            max-width: min(260px, 70vw);
            object-fit: contain;
            border-radius: 10px;
            background: #000;
            display: block;
        }
        .nexa-brand-name {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: .03em;
            line-height: 1.1;
        }
        .nexa-brand-sub {
            margin-top: 3px;
            font-size: 11.5px;
            color: var(--nexa-muted);
            font-weight: 500;
        }
        .nexa-left-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 420px;
            padding: 28px 0 18px;
        }
        .nexa-headline {
            margin: 0 0 16px;
            font-size: clamp(36px, 4.2vw, 52px);
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.05;
            text-shadow: 0 2px 24px rgba(0, 0, 0, .35);
        }
        .nexa-headline em {
            font-style: normal;
            background: linear-gradient(90deg, #d8b4fe 0%, #818cf8 48%, #60a5fa 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .nexa-lead {
            margin: 0 0 30px;
            max-width: 36ch;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
            font-weight: 500;
        }
        .nexa-features {
            display: grid;
            gap: 18px;
        }
        .nexa-feature {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 12px;
            align-items: flex-start;
        }
        .nexa-feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            border: 1px solid transparent;
            backdrop-filter: blur(8px);
        }
        .nexa-feature-icon svg { width: 18px; height: 18px; }
        .nexa-feature-icon.is-purple { background: rgba(168, 85, 247, .2); color: #d8b4fe; border-color: rgba(168, 85, 247, .35); }
        .nexa-feature-icon.is-blue { background: rgba(59, 130, 246, .2); color: #93c5fd; border-color: rgba(59, 130, 246, .35); }
        .nexa-feature-icon.is-green { background: rgba(16, 185, 129, .18); color: #6ee7b7; border-color: rgba(16, 185, 129, .35); }
        .nexa-feature-icon.is-orange { background: rgba(249, 115, 22, .18); color: #fdba74; border-color: rgba(249, 115, 22, .35); }
        .nexa-feature strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 2px;
        }
        .nexa-feature span {
            display: block;
            font-size: 12.5px;
            color: #94a3b8;
            line-height: 1.45;
        }
        .nexa-left-foot {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 12.5px;
            font-weight: 500;
        }
        .nexa-left-foot svg { width: 15px; height: 15px; color: #34d399; flex-shrink: 0; }

        /* Right login */
        .nexa-right {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 28px 28px;
            background:
                radial-gradient(520px 320px at 50% 30%, rgba(88, 28, 135, 0.2), transparent 65%),
                linear-gradient(180deg, #0a0b14 0%, #06070c 100%);
            border-left: 1px solid rgba(148, 163, 184, 0.08);
        }
        .nexa-card {
            width: 100%;
            max-width: 420px;
            background: linear-gradient(180deg, rgba(24, 26, 38, 0.97), rgba(16, 18, 28, 0.99));
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 22px;
            padding: 36px 32px 30px;
            box-shadow:
                0 0 0 1px rgba(168, 85, 247, 0.05),
                0 24px 60px rgba(0, 0, 0, 0.45),
                0 0 80px rgba(88, 28, 135, 0.12);
        }
        .nexa-lock {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: #e9d5ff;
            background: radial-gradient(circle, rgba(168, 85, 247, .28), rgba(168, 85, 247, .06) 72%);
            border: 1.5px solid rgba(192, 132, 252, .55);
            box-shadow:
                0 0 28px rgba(168, 85, 247, .35),
                0 0 0 10px rgba(168, 85, 247, .08),
                0 0 0 20px rgba(168, 85, 247, .04);
        }
        .nexa-lock svg { width: 26px; height: 26px; }
        .nexa-card-title {
            margin: 0;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .nexa-card-sub {
            margin: 8px 0 0;
            text-align: center;
            color: var(--nexa-muted);
            font-size: 13.5px;
            font-weight: 500;
        }
        .nexa-alert {
            margin-top: 20px;
            border: 1px solid rgba(244, 63, 94, .35);
            background: rgba(244, 63, 94, .1);
            color: #fecdd3;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
        }
        .nexa-alert strong { display: block; color: #fff; margin-bottom: 2px; }
        .nexa-form { margin-top: 26px; display: grid; gap: 16px; }
        .nexa-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 7px;
        }
        .nexa-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 7px;
        }
        .nexa-label-row .nexa-label { margin-bottom: 0; }
        .nexa-forgot {
            font-size: 12.5px;
            font-weight: 600;
            color: #c084fc;
            text-decoration: none;
        }
        .nexa-forgot:hover { color: #e9d5ff; }
        .nexa-field { position: relative; }
        .nexa-field input {
            width: 100%;
            height: 46px;
            border-radius: 11px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(7, 8, 14, 0.75);
            color: #f8fafc;
            padding: 0 42px 0 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .nexa-field input::placeholder { color: #64748b; }
        .nexa-field input:focus {
            border-color: rgba(168, 85, 247, .55);
            box-shadow: 0 0 0 3px rgba(168, 85, 247, .15);
        }
        .nexa-field-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #64748b;
            pointer-events: none;
        }
        .nexa-check {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--nexa-muted);
            font-size: 13px;
            font-weight: 500;
            user-select: none;
        }
        .nexa-check input {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            accent-color: #a855f7;
            cursor: pointer;
        }
        .nexa-submit {
            margin-top: 4px;
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            font-size: 14.5px;
            font-weight: 750;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(90deg, #9333ea 0%, #6366f1 48%, #2563eb 100%);
            box-shadow: 0 12px 28px rgba(99, 102, 241, .35);
            transition: filter .15s ease, transform .15s ease;
        }
        .nexa-submit:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .nexa-submit svg { width: 18px; height: 18px; }
        .nexa-right-foot {
            margin-top: 28px;
            text-align: center;
            color: var(--nexa-soft);
            font-size: 12px;
        }
        .nexa-status {
            margin-top: 16px;
            border-radius: 12px;
            border: 1px solid rgba(52, 211, 153, .3);
            background: rgba(16, 185, 129, .1);
            color: #a7f3d0;
            padding: 10px 12px;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .nexa-shell { grid-template-columns: 1fr; }
            .nexa-left { min-height: 0; }
            .nexa-left-content {
                min-height: auto;
                padding: 28px 22px 20px;
            }
            .nexa-left-poster-img { object-position: center 30%; min-height: 420px; }
            .nexa-left-scrim {
                background:
                    linear-gradient(180deg,
                        rgba(6, 7, 12, 0.55) 0%,
                        rgba(6, 7, 12, 0.72) 45%,
                        rgba(6, 7, 12, 0.92) 100%);
            }
            .nexa-left-body { padding: 20px 0 14px; max-width: none; }
            .nexa-headline { font-size: 32px; }
            .nexa-right {
                border-left: 0;
                border-top: 1px solid rgba(148, 163, 184, 0.08);
                padding-top: 24px;
            }
        }
        @media (max-width: 520px) {
            .nexa-card { padding: 28px 18px 22px; border-radius: 18px; }
            .nexa-features { gap: 14px; }
            .nexa-left-foot { font-size: 11.5px; }
        }
    </style>
</head>
<body class="nexa-login antialiased">
    {{ $slot }}
</body>
</html>
