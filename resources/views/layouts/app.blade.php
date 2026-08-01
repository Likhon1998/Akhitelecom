<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nexa POS') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="admin-panel font-sans antialiased text-slate-900 bg-[#F4F6FB]">
    @php
        $adminFlash = array_filter([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ], fn ($v) => filled($v));
    @endphp
    <script>
        window.__ADMIN_FLASH__ = @json($adminFlash);
    </script>

    <div id="admin-progress" class="admin-progress" aria-hidden="true">
        <div id="admin-progress-bar" class="admin-progress__bar"></div>
        <div id="admin-progress-peg" class="admin-progress__peg"></div>
    </div>

    <div x-data="{ sidebarOpen: false }"
         @keydown.escape.window="sidebarOpen = false"
         class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="admin-scroll-hide relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden">
            @isset($header)
                <header class="bg-white/80 backdrop-blur border-b border-slate-100 mt-16 lg:mt-[4.25rem]">
                    <div class="max-w-[1800px] mx-auto py-3 px-3 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="w-full grow p-3 sm:p-4 lg:p-5 min-w-0 {{ !isset($header) ? 'mt-16 lg:mt-[4.25rem]' : '' }}">
                <div class="w-full max-w-[1800px] mx-auto min-w-0">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script>
        function onlineOrderBell(listUrl, seenUrl) {
            return {
                panelOpen: false,
                loading: false,
                loaded: false,
                unread: 0,
                items: [],
                init() {
                    this.panelOpen = false;
                    this.fetchBadge();
                    setInterval(() => this.fetchBadge(), 60000);
                },
                togglePanel(event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) {
                        this.fetchList(true);
                    } else {
                        this.markSeen();
                    }
                },
                closePanel() {
                    if (!this.panelOpen) return;
                    this.panelOpen = false;
                    this.markSeen();
                },
                async fetchBadge() {
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.unread = Number(data.unread || 0);
                    } catch (e) {}
                },
                async fetchList(force = false) {
                    if (this.loading || (this.loaded && !force)) return;
                    this.loading = true;
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.items = data.items || [];
                        this.unread = Number(data.unread || 0);
                        this.loaded = true;
                    } catch (e) {
                    } finally {
                        this.loading = false;
                    }
                },
                async markSeen() {
                    if (!this.items.some((item) => item.is_new)) return;
                    try {
                        await fetch(seenUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        this.items = this.items.map((item) => ({ ...item, is_new: false }));
                        this.unread = 0;
                    } catch (e) {}
                },
            };
        }

        setInterval(function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            } else {
                fetch('/refresh-session', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).catch(function () {});
            }
        }, 10 * 60 * 1000);

        window.addEventListener('pageshow', function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            }
        });


        /** Open POS in a large counter window (not a tiny popup/tab). */
        window.launchPosTerminal = function (url) {
            url = url || @json(route('pos.index'));
            var w = screen.availWidth || screen.width || 1280;
            var h = screen.availHeight || screen.height || 800;
            var features = 'popup=yes,width=' + w + ',height=' + h + ',left=0,top=0,resizable=yes,scrollbars=yes';
            var win = window.open(url, 'nexa_pos_terminal', features);
            if (!win) {
                window.location.href = url;
                return false;
            }
            try {
                win.focus();
                win.moveTo(0, 0);
                win.resizeTo(w, h);
            } catch (e) {}
            return false;
        };
    </script>
</body>
</html>
