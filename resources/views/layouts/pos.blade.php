<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Terminal — {{ config('app.name', 'Nexa POS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { height: 100%; width: 100%; margin: 0; overflow: hidden; }
    </style>
</head>
<body class="h-full overflow-hidden font-sans antialiased text-gray-900 bg-slate-100">
    @php
        $adminFlash = array_filter([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ], fn ($v) => filled($v));
    @endphp
    <script>window.__ADMIN_FLASH__ = @json($adminFlash);</script>
    {{ $slot }}
    <script>
        setInterval(function () {
            fetch('/refresh-session', { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(function () {});
        }, 15 * 60 * 1000);

        // Keep counter window as large as the OS allows
        (function expandPosWindow() {
            try {
                if (window.name === 'nexa_pos_terminal') {
                    window.moveTo(0, 0);
                    window.resizeTo(screen.availWidth || screen.width, screen.availHeight || screen.height);
                }
            } catch (e) {}
        })();
    </script>
</body>
</html>