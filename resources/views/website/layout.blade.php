<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Available before Vite loads — prevents CSRF mismatch on fast register/login/checkout.
        (function () {
            window.syncCsrfToken = window.syncCsrfToken || function (tokenFromServer) {
                const meta = document.head.querySelector('meta[name="csrf-token"]');
                const token = tokenFromServer || meta?.content;
                if (!token) return null;
                if (meta) meta.content = token;
                document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = token; });
                return token;
            };
            window.currentCsrfToken = window.currentCsrfToken || function () {
                return document.head.querySelector('meta[name="csrf-token"]')?.content || '';
            };
            window.refreshCsrfToken = window.refreshCsrfToken || async function () {
                try {
                    const res = await fetch(@json(route('csrf.token')), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    if (!res.ok) return window.syncCsrfToken();
                    const data = await res.json();
                    return window.syncCsrfToken(data?.csrf_token);
                } catch (e) {
                    return window.syncCsrfToken();
                }
            };
            window.fetchJsonWithCsrf = window.fetchJsonWithCsrf || async function (url, options = {}, retried = false) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                };
                if (method !== 'GET' && method !== 'HEAD') {
                    if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
                        headers['Content-Type'] = 'application/json';
                    }
                    headers['X-CSRF-TOKEN'] = window.currentCsrfToken();
                }
                const res = await fetch(url, { credentials: 'same-origin', ...options, method, headers });
                if (res.status === 419 && !retried) {
                    let token = null;
                    try { token = (await res.clone().json())?.csrf_token || null; } catch (e) {}
                    if (token) window.syncCsrfToken(token);
                    else await window.refreshCsrfToken();
                    return window.fetchJsonWithCsrf(url, options, true);
                }
                return res;
            };
        })();
    </script>
    <title>@yield('title', $settings->store_name ?? config('app.name', 'Akhi Telecom'))</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @php
        $storefrontUser = auth()->check() && auth()->user()->isStorefrontCustomer()
            ? [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->customerProfile?->phone ?? '',
                'address' => auth()->user()->customerProfile?->address ?? '',
            ]
            : null;
    @endphp
    <script>
        function readStorefrontList(key) {
            try {
                const raw = localStorage.getItem(key);
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function headerSearch(suggestUrl, shopBaseUrl, currencySymbol) {
            return {
                q: @json(request('search', '')),
                category: @json(request('category', '')),
                open: false,
                loading: false,
                products: [],
                mode: 'best',
                activeIndex: -1,
                currency: currencySymbol || '$',
                _req: 0,
                get shopUrl() {
                    const params = new URLSearchParams();
                    if (this.q) params.set('search', this.q);
                    if (this.category) params.set('category', this.category);
                    const qs = params.toString();
                    return qs ? `${shopBaseUrl}?${qs}` : shopBaseUrl;
                },
                onFocus() {
                    this.open = true;
                    this.fetchSuggestions();
                },
                async fetchSuggestions() {
                    this.open = true;
                    this.loading = true;
                    this.activeIndex = -1;
                    const req = ++this._req;
                    try {
                        const params = new URLSearchParams({
                            q: this.q || '',
                            category: this.category || '',
                            limit: '8',
                        });
                        const res = await fetch(`${suggestUrl}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        if (req !== this._req) return;
                        this.products = Array.isArray(data.products) ? data.products : [];
                        this.mode = data.mode || (this.q ? 'search' : 'best');
                    } catch (e) {
                        if (req !== this._req) return;
                        this.products = [];
                    } finally {
                        if (req === this._req) this.loading = false;
                    }
                },
                move(step) {
                    if (!this.products.length) return;
                    this.open = true;
                    const len = this.products.length;
                    this.activeIndex = (this.activeIndex + step + len) % len;
                },
                onEnter(event) {
                    if (this.open && this.activeIndex >= 0 && this.products[this.activeIndex]) {
                        event.preventDefault();
                        window.location.href = this.products[this.activeIndex].url;
                    }
                },
            };
        }
        window.headerSearch = headerSearch;

        function storefrontCart() {
            return {
                cart: readStorefrontList('gaget_cart'),
                wishlist: readStorefrontList('gaget_wishlist'),
                cartOpen: false,
                checkoutOpen: false,
                mobileOpen: false,
                mobileCatsOpen: false,
                mobileBrandsOpen: false,
                checkoutStep: 'auth',
                authPurpose: 'account',
                authTab: 'login',
                authLoading: false,
                authMessage: '',
                authMessageOk: false,
                ordering: false,
                orderMessage: '',
                lastOrderId: null,
                lastOrderInvoice: '',
                redirectSeconds: 0,
                _redirectTimer: null,
                orderSuccess: false,
                toastMessage: '',
                toastVisible: false,
                isLoggedIn: @json((bool) $storefrontUser),
                currency: @json($settings->currency_symbol ?? '$'),
                checkout: {
                    name: @json(data_get($storefrontUser, 'name', '')),
                    phone: @json(data_get($storefrontUser, 'phone', '')),
                    address: @json(data_get($storefrontUser, 'address', '')),
                },
                authLogin: { email: '', password: '' },
                authRegister: { name: '', phone: '', email: '', password: '', address: '' },
                get cartCount() { return this.cart.reduce((s, i) => s + (Number(i.qty) || 0), 0); },
                get cartTotal() { return this.cart.reduce((s, i) => s + (Number(i.price) || 0) * (Number(i.qty) || 0), 0); },
                get wishlistCount() { return this.wishlist.length; },
                save() { localStorage.setItem('gaget_cart', JSON.stringify(this.cart)); },
                saveWishlist() { localStorage.setItem('gaget_wishlist', JSON.stringify(this.wishlist)); },
                addToCart(product, qty = 1, openDrawer = true) {
                    if (!product || product.id === undefined || product.id === null) return;

                    const id = Number(product.id);
                    const amount = Math.max(1, Number(qty) || 1);
                    const next = this.cart.map((item) => ({ ...item }));
                    const existing = next.find((item) => Number(item.id) === id);

                    if (existing) {
                        existing.qty = (Number(existing.qty) || 0) + amount;
                    } else {
                        next.push({
                            id,
                            name: product.name || 'Product',
                            price: Number(product.price) || 0,
                            image: product.image || '',
                            qty: amount,
                        });
                    }

                    this.cart = next;
                    this.save();
                    if (openDrawer) this.cartOpen = true;
                },
                updateQty(i, d) {
                    const next = this.cart.map((item) => ({ ...item }));
                    next[i].qty = (Number(next[i].qty) || 0) + d;
                    this.cart = next[i].qty <= 0 ? next.filter((_, idx) => idx !== i) : next;
                    this.save();
                },
                removeItem(i) {
                    this.cart = this.cart.filter((_, idx) => idx !== i);
                    this.save();
                },
                inWishlist(id) { return this.wishlist.some((i) => Number(i.id) === Number(id)); },
                toggleWishlist(product) {
                    const idx = this.wishlist.findIndex((i) => Number(i.id) === Number(product.id));
                    if (idx >= 0) this.wishlist.splice(idx, 1);
                    else this.wishlist.push(product);
                    this.saveWishlist();
                },
                flashToast(message) {
                    this.toastMessage = message;
                    this.toastVisible = true;
                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => { this.toastVisible = false; }, 2200);
                },
                prefillCheckout(profile) {
                    this.checkout.name = profile?.name || '';
                    this.checkout.phone = profile?.phone || '';
                    this.checkout.address = profile?.address || '';
                },
                startCheckout() {
                    if (this.cart.length === 0) return;
                    this.cartOpen = false;
                    this.checkoutOpen = true;
                    this.authPurpose = 'checkout';
                    this.orderMessage = '';
                    this.orderSuccess = false;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    if (this.isLoggedIn) {
                        this.checkoutStep = 'order';
                    } else {
                        this.checkoutStep = 'auth';
                        this.authTab = 'login';
                    }
                },
                openSignIn(tab = 'login') {
                    this.cartOpen = false;
                    this.checkoutOpen = true;
                    this.authPurpose = 'account';
                    this.checkoutStep = 'auth';
                    this.authTab = tab === 'register' ? 'register' : 'login';
                    this.authMessage = '';
                    this.authMessageOk = false;
                    this.orderMessage = '';
                    this.orderSuccess = false;
                },
                afterAuthSuccess(user) {
                    this.isLoggedIn = true;
                    this.authMessageOk = false;
                    this.prefillCheckout(user);
                    if (this.authPurpose === 'checkout') {
                        this.checkoutStep = 'order';
                        if (window.GagetLoader) window.GagetLoader.hide();
                        return;
                    }
                    // Return to the same page they signed in from
                    if (window.GagetLoader) window.GagetLoader.show('Welcome back');
                    window.location.reload();
                },
                async submitLogin() {
                    this.authLoading = true;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    if (window.GagetLoader) window.GagetLoader.show('Signing you in');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.account.login')), {
                            method: 'POST',
                            body: JSON.stringify(this.authLogin),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (!res.ok) {
                            this.authMessage = data.message || data.errors?.email?.[0] || (res.status === 419 ? 'Session expired. Please try signing in again.' : 'Sign in failed.');
                            if (window.GagetLoader) window.GagetLoader.hide();
                            return;
                        }
                        this.afterAuthSuccess(data.user);
                    } catch (e) {
                        this.authMessage = 'Network error. Please try again.';
                        if (window.GagetLoader) window.GagetLoader.hide();
                    } finally {
                        this.authLoading = false;
                    }
                },
                async submitRegister() {
                    if (!this.authRegister.name || !this.authRegister.phone || !this.authRegister.email || !this.authRegister.password) {
                        this.authMessage = 'Please fill name, phone, email, and password.';
                        this.authMessageOk = false;
                        return;
                    }
                    this.authLoading = true;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    if (window.GagetLoader) window.GagetLoader.show('Creating your account');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.account.register')), {
                            method: 'POST',
                            body: JSON.stringify(this.authRegister),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (!res.ok) {
                            const err = data.errors || {};
                            this.authMessage = err.name?.[0] || err.email?.[0] || err.phone?.[0] || err.password?.[0] || data.message || (res.status === 419 ? 'Session expired. Please try again.' : 'Registration failed.');
                            return;
                        }
                        // Registration only creates the account — user must sign in to order.
                        this.isLoggedIn = false;
                        this.authLogin.email = data.email || this.authRegister.email || '';
                        this.authLogin.password = '';
                        this.authRegister = { name: '', phone: '', email: '', password: '', address: '' };
                        this.authTab = 'login';
                        this.authMessageOk = true;
                        this.authMessage = data.message || 'Account created. Please sign in to continue.';
                    } catch (e) {
                        this.authMessage = 'Network error. Please try again.';
                    } finally {
                        this.authLoading = false;
                        if (window.GagetLoader) window.GagetLoader.hide();
                    }
                },
                async placeOrder() {
                    if (!this.checkout.name || !this.checkout.phone || !this.checkout.address) {
                        this.orderMessage = 'Name, phone, and delivery address are required.';
                        this.orderSuccess = false;
                        return;
                    }
                    if (!this.isLoggedIn) {
                        this.checkoutStep = 'auth';
                        this.authTab = 'login';
                        return;
                    }
                    this.ordering = true;
                    this.orderMessage = '';
                    if (window.GagetLoader) window.GagetLoader.show('Placing your order');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.checkout')), {
                            method: 'POST',
                            body: JSON.stringify({
                                cart: this.cart,
                                customer_name: this.checkout.name,
                                customer_phone: this.checkout.phone,
                                customer_address: this.checkout.address,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (res.status === 419) {
                            this.orderSuccess = false;
                            this.orderMessage = 'Session expired. Please try placing the order again.';
                            return;
                        }
                        if (res.status === 401 || data.auth_required) {
                            this.isLoggedIn = false;
                            this.checkoutStep = 'auth';
                            this.authTab = 'login';
                            this.authMessage = 'Please sign in to place your order.';
                            return;
                        }
                        if (data.success) {
                            if (!data.invoice && !data.order_id) {
                                this.orderSuccess = false;
                                this.orderMessage = 'Order placed but no Order ID was returned. Please check My Orders or contact support.';
                                return;
                            }
                            this.cart = [];
                            this.save();
                            this.orderSuccess = true;
                            this.lastOrderId = data.order_id || null;
                            this.lastOrderInvoice = data.invoice || '';
                            this.orderMessage = '';
                            this.checkoutStep = 'success';
                            this.startAccountRedirect();
                        } else {
                            this.orderSuccess = false;
                            this.orderMessage = data.message || 'Order failed.';
                        }
                    } catch (e) {
                        this.orderSuccess = false;
                        this.orderMessage = 'Network error.';
                    }
                    this.ordering = false;
                    if (window.GagetLoader) window.GagetLoader.hide();
                },
                startAccountRedirect() {
                    if (this._redirectTimer) clearInterval(this._redirectTimer);
                    this.redirectSeconds = 3;
                    const accountUrl = @json(route('website.account'));
                    const go = () => {
                        if (this._redirectTimer) clearInterval(this._redirectTimer);
                        if (window.GagetLoader) window.GagetLoader.show('Opening your orders');
                        const params = new URLSearchParams({ placed: '1' });
                        if (this.lastOrderInvoice) params.set('order', this.lastOrderInvoice);
                        else if (this.lastOrderId) params.set('oid', String(this.lastOrderId));
                        window.location.href = accountUrl + '?' + params.toString() + '#recent-orders';
                    };
                    this._redirectTimer = setInterval(() => {
                        this.redirectSeconds -= 1;
                        if (this.redirectSeconds <= 0) go();
                    }, 1000);
                },
                goToAccountNow() {
                    if (this._redirectTimer) clearInterval(this._redirectTimer);
                    this.redirectSeconds = 0;
                    if (window.GagetLoader) window.GagetLoader.show('Opening your orders');
                    const accountUrl = @json(route('website.account'));
                    const params = new URLSearchParams({ placed: '1' });
                    if (this.lastOrderInvoice) params.set('order', this.lastOrderInvoice);
                    else if (this.lastOrderId) params.set('oid', String(this.lastOrderId));
                    window.location.href = accountUrl + '?' + params.toString() + '#recent-orders';
                },
                jsonHeaders() {
                    return {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (window.currentCsrfToken && window.currentCsrfToken())
                            || document.querySelector('meta[name=csrf-token]')?.content
                            || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                },
                async csrfFetch(url, options = {}) {
                    if (typeof window.fetchJsonWithCsrf === 'function') {
                        return window.fetchJsonWithCsrf(url, options);
                    }
                    return fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.jsonHeaders(), ...(options.headers || {}) } });
                },
                applyCsrfFromResponse(data) {
                    if (data?.csrf_token && typeof window.syncCsrfToken === 'function') {
                        window.syncCsrfToken(data.csrf_token);
                    }
                },
            };
        }
        window.storefrontCart = storefrontCart;
    </script>
    @vite(['resources/css/app.css', 'resources/css/website.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        /* Critical first-paint loader (before Vite CSS) */
        .gaget-page-loader{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(248,250,252,.94);transition:opacity .38s ease,visibility .38s ease}
        .gaget-page-loader.is-hidden{opacity:0;visibility:hidden;pointer-events:none}
        /* Safety: brand logos must never render at intrinsic SVG/PNG size */
        .gaget-store .tn-brand-logo{height:40px!important;max-height:40px!important;max-width:112px!important;width:auto!important;object-fit:contain!important}
        .gaget-store .tn-brand-logo-frame{height:44px!important;max-width:120px!important;overflow:hidden!important}
        .gaget-store .pd-brand-logo{height:18px!important;max-height:18px!important;max-width:88px!important;width:auto!important;object-fit:contain!important}
        .tn-footer-credit{border-top:1px solid #eef2f7;padding:14px 16px 18px;text-align:center;background:#f8f9fc}
        .powered-by,.powered-by--footer{margin:0;font-size:12px;font-weight:500;letter-spacing:.02em;color:#94a3b8}
        .powered-by strong,.powered-by--footer strong{color:#0f172a;font-weight:800}
    </style>
</head>
<body class="gaget-store bg-white antialiased" id="storefront-root" x-data="storefrontCart()" @keydown.escape.window="cartOpen=false; checkoutOpen=false; mobileOpen=false; mobileCatsOpen=false; mobileBrandsOpen=false">

{{-- Joyful full-page loader (shown on first paint + every navigation) --}}
<div id="gaget-page-loader" class="gaget-page-loader is-active" role="status" aria-live="polite" aria-busy="true" aria-label="Loading">
    <div class="gaget-page-loader__bar" aria-hidden="true"></div>
    <div class="gaget-page-loader__inner">
        <div class="gaget-page-loader__stage" aria-hidden="true">
            <div class="gaget-page-loader__orbit">
                <span class="gaget-page-loader__spark"></span>
                <span class="gaget-page-loader__spark"></span>
                <span class="gaget-page-loader__spark"></span>
            </div>
            <div class="gaget-page-loader__bag"></div>
        </div>
        <p class="gaget-page-loader__text">
            {{ $settings->store_name ?? config('app.name', 'Akhi Telecom') }}
            <span class="gaget-page-loader__dots" aria-hidden="true"><span></span><span></span><span></span></span>
        </p>
        <p class="gaget-page-loader__sub" id="gaget-loader-msg">Getting things ready</p>
    </div>
</div>

@include('website.partials.header')

@auth
    <form id="storefront-logout-form" method="POST" action="{{ route('website.account.logout') }}" class="hidden" aria-hidden="true">
        @csrf
    </form>
    <script>
        window.storefrontLogout = function () {
            const form = document.getElementById('storefront-logout-form');
            if (form) form.submit();
        };
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
@endauth

<main>
    @yield('content')
</main>

@include('website.partials.footer')

{{-- Cart drawer --}}
<div x-show="cartOpen" x-cloak class="fixed inset-0 z-[70]">
    <div class="absolute inset-0 bg-black/50" @click="cartOpen=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between p-5 border-b">
            <h3 class="text-lg font-bold">Your Cart (<span x-text="cartCount"></span>)</h3>
            <button @click="cartOpen=false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <template x-if="cart.length===0"><p class="text-gray-500 text-center py-10">Your cart is empty.</p></template>
            <template x-for="(item,i) in cart" :key="item.id">
                <div class="flex gap-3 border rounded-xl p-3">
                    <img :src="item.image" class="w-16 h-16 object-cover rounded-lg bg-gray-50" alt="">
                    <div class="flex-1">
                        <p class="font-semibold text-sm" x-text="item.name"></p>
                        <p class="text-blue-600 font-bold text-sm" x-text="currency+item.price.toFixed(2)"></p>
                        <div class="flex items-center gap-2 mt-2">
                            <button @click="updateQty(i,-1)" class="w-7 h-7 rounded bg-gray-100 font-bold">−</button>
                            <span x-text="item.qty" class="text-sm font-medium w-6 text-center"></span>
                            <button @click="updateQty(i,1)" class="w-7 h-7 rounded bg-gray-100 font-bold">+</button>
                            <button @click="removeItem(i)" class="ml-auto text-red-500 text-xs font-semibold">Remove</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="border-t p-5" x-show="cart.length>0">
            <div class="flex justify-between font-bold text-lg mb-3"><span>Total</span><span x-text="currency+cartTotal.toFixed(2)"></span></div>
            <button @click="startCheckout()" class="w-full gaget-btn-primary text-center">Checkout</button>
        </div>
    </div>
</div>

{{-- Checkout: sign in / register / place order (one modal) --}}
<div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/50" @click="checkoutStep !== 'success' && (checkoutOpen=false)"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-md max-h-[92vh] overflow-y-auto p-4 sm:p-6"
         :class="checkoutStep === 'success' && 'overflow-hidden'">
        <button type="button" x-show="checkoutStep !== 'success'" @click="checkoutOpen=false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>

        {{-- Auth step (standalone account OR checkout) --}}
        <div x-show="checkoutStep==='auth'" x-cloak>
            <h3 class="text-xl font-bold text-slate-900 pr-8" x-text="authPurpose === 'checkout' ? 'Sign in to checkout' : (authTab === 'register' ? 'Create your account' : 'Welcome back')"></h3>
            <p class="text-sm text-slate-500 mt-1 mb-5" x-text="authPurpose === 'checkout' ? 'Create an account if needed, then sign in to place your order.' : 'Create an account anytime — then sign in to shop and track orders.'"></p>

            <div class="flex rounded-xl bg-slate-100 p-1 mb-5">
                <button type="button" @click="authTab='login'; authMessage=''; authMessageOk=false" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="authTab==='login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Sign in</button>
                <button type="button" @click="authTab='register'; authMessage=''; authMessageOk=false" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="authTab==='register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Create account</button>
            </div>

            <div x-show="authTab==='login'" class="space-y-3">
                <input x-model="authLogin.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="email">
                <input x-model="authLogin.password" type="password" placeholder="Password" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="current-password" @keydown.enter.prevent="submitLogin()">
                <button type="button" @click="submitLogin()" :disabled="authLoading" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="authLoading ? 'Signing in...' : (authPurpose === 'checkout' ? 'Sign in & continue' : 'Sign in')"></span>
                </button>
            </div>

            <div x-show="authTab==='register'" class="space-y-3">
                <input x-model="authRegister.name" type="text" placeholder="Full name" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="name">
                <input x-model="authRegister.phone" type="text" placeholder="Phone number" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="tel">
                <input x-model="authRegister.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="email">
                <input x-model="authRegister.password" type="password" placeholder="Password (min. 8 characters)" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="new-password">
                <textarea x-model="authRegister.address" placeholder="Address (optional)" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" rows="2"></textarea>
                <button type="button" @click="submitRegister()" :disabled="authLoading" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="authLoading ? 'Creating account...' : 'Create account'"></span>
                </button>
            </div>

            <p x-show="authMessage" x-text="authMessage" class="mt-3 text-sm text-center" :class="authMessageOk ? 'text-emerald-600' : 'text-rose-600'"></p>
        </div>

        {{-- Order step --}}
        <div x-show="checkoutStep==='order'" x-cloak>
            <h3 class="text-xl font-bold text-slate-900 pr-8">Place order</h3>
            <p class="text-sm text-slate-500 mt-1 mb-5">Cash on delivery · <span x-text="cartCount"></span> item(s) · <span class="font-semibold text-slate-800" x-text="currency+cartTotal.toFixed(2)"></span></p>

            <div class="space-y-3">
                <input x-model="checkout.name" type="text" placeholder="Full name" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">
                <input x-model="checkout.phone" type="text" placeholder="Phone number" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">
                <textarea x-model="checkout.address" placeholder="Delivery address" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" rows="2"></textarea>
                <button type="button" @click="placeOrder()" :disabled="ordering" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="ordering ? 'Placing order...' : 'Confirm order (Cash on delivery)'"></span>
                </button>
            </div>

            <p x-show="orderMessage" x-text="orderMessage" class="mt-3 text-sm text-center" :class="orderSuccess ? 'text-green-600' : 'text-red-600'"></p>
        </div>

        {{-- Success step --}}
        <div x-show="checkoutStep==='success'" x-cloak class="gaget-order-success text-center py-2">
            <div class="gaget-order-success__burst" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="gaget-order-success__check mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900">Order placed successfully!</h3>
            <p class="mt-2 text-sm text-slate-500">Thank you — your order is confirmed and cash on delivery.</p>
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-gradient-to-b from-emerald-50 to-white px-4 py-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Your Order ID</p>
                <p class="mt-1.5 font-mono text-[17px] font-extrabold tracking-wide text-emerald-950" x-text="lastOrderInvoice || ('#' + lastOrderId)"></p>
                <p class="mt-2 text-[12px] text-emerald-800/80">Keep this ID for tracking and support.</p>
            </div>
            <p class="mt-4 text-[12px] text-slate-500">
                Taking you to <span class="font-semibold text-slate-700">My Orders</span>
                <span x-show="redirectSeconds > 0"> in <span class="font-bold text-blue-600" x-text="redirectSeconds"></span>s…</span>
            </p>
            <div class="mt-5 flex flex-col gap-2">
                <button type="button" @click="goToAccountNow()" class="gaget-btn-primary w-full text-center text-sm py-3">
                    View my order now
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div x-show="toastVisible" x-cloak x-transition.opacity.duration.200ms
     class="fixed bottom-6 left-1/2 z-[90] -translate-x-1/2 rounded-xl bg-slate-900 px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg">
    <span x-text="toastMessage"></span>
</div>

<script>
window.getStorefrontRoot = function () {
    const root = document.getElementById('storefront-root') || document.body;
    if (!window.Alpine || typeof Alpine.$data !== 'function') return null;
    try {
        return Alpine.$data(root);
    } catch (e) {
        return null;
    }
};

window.addProductToCart = function (product, qty = 1, openDrawer = true) {
    const root = window.getStorefrontRoot();
    if (root && typeof root.addToCart === 'function') {
        root.addToCart(product, qty, openDrawer);
        return true;
    }
    return false;
};

document.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-add-to-cart]');
    if (!btn) return;

    event.preventDefault();
    event.stopPropagation();

    let product;
    try {
        product = JSON.parse(btn.getAttribute('data-add-to-cart'));
    } catch (e) {
        return;
    }

    const qtyAttr = btn.getAttribute('data-qty');
    const qty = Math.max(1, Number(qtyAttr || 1) || 1);
    const openDrawer = btn.getAttribute('data-open-cart') !== '0';
    const goCheckout = btn.getAttribute('data-checkout') === '1';

    const finish = function () {
        if (goCheckout) {
            const root = window.getStorefrontRoot();
            if (root && typeof root.startCheckout === 'function') {
                root.startCheckout();
            }
        }
    };

    if (window.addProductToCart(product, qty, goCheckout ? false : openDrawer)) {
        finish();
        return;
    }

    // Alpine not ready yet — wait and add once so the badge shows "1" immediately after boot.
    let tries = 0;
    const timer = setInterval(function () {
        tries += 1;
        if (window.addProductToCart(product, qty, goCheckout ? false : openDrawer)) {
            clearInterval(timer);
            finish();
        } else if (tries > 50) {
            clearInterval(timer);
        }
    }, 40);
});
</script>
<script>
(function () {
    const el = document.getElementById('gaget-page-loader');
    const msgEl = document.getElementById('gaget-loader-msg');
    if (!el) return;

    const messages = [
        'Getting things ready',
        'Almost there',
        'Finding great picks',
        'Packing the goodies',
        'Just a moment',
        'Making it snappy',
    ];
    let shownAt = Date.now();
    let msgTimer = null;
    let msgIndex = 0;
    let hideTimer = null;
    const MIN_MS = 420;

    function setMessage(text) {
        if (msgEl && text) msgEl.textContent = text;
    }

    function startMessages() {
        stopMessages();
        msgIndex = Math.floor(Math.random() * messages.length);
        setMessage(messages[msgIndex]);
        msgTimer = setInterval(function () {
            msgIndex = (msgIndex + 1) % messages.length;
            setMessage(messages[msgIndex]);
        }, 1400);
    }

    function stopMessages() {
        if (msgTimer) {
            clearInterval(msgTimer);
            msgTimer = null;
        }
    }

    function show(message) {
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        shownAt = Date.now();
        el.classList.add('is-active');
        el.classList.remove('is-hidden');
        el.setAttribute('aria-busy', 'true');
        if (message) setMessage(message);
        else startMessages();
    }

    function hide() {
        const wait = Math.max(0, MIN_MS - (Date.now() - shownAt));
        if (hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            stopMessages();
            el.classList.add('is-hidden');
            el.classList.remove('is-active');
            el.setAttribute('aria-busy', 'false');
            hideTimer = null;
        }, wait);
    }

    window.GagetLoader = { show: show, hide: hide };

    // First paint: hide after page is ready
    function onReady() {
        if (document.readyState === 'complete') hide();
        else window.addEventListener('load', hide, { once: true });
    }
    onReady();

    // Back/forward cache
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hide();
    });

    function shouldInterceptLink(a, event) {
        if (!a || !a.href) return false;
        if (a.target && a.target !== '_self') return false;
        if (a.hasAttribute('download')) return false;
        if (a.dataset.noLoader !== undefined) return false;
        if (event.defaultPrevented) return false;
        if (event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

        let url;
        try { url = new URL(a.href, window.location.href); }
        catch (e) { return false; }

        if (url.origin !== window.location.origin) return false;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;
        // Same URL (no navigation)
        if (url.href.split('#')[0] === window.location.href.split('#')[0]) return false;

        return true;
    }

    document.addEventListener('click', function (event) {
        const a = event.target.closest('a[href]');
        if (!shouldInterceptLink(a, event)) return;
        show();
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noLoader !== undefined) return;
        if (form.target && form.target !== '_self') return;
        // AJAX forms often preventDefault — skip those
        // We show anyway; if submit is cancelled shortly after, pageshow won't fire — use short timeout fallback
        show('Saving your request');
        setTimeout(function () {
            // If still on same page after a short wait (AJAX / validation stay), hide
            if (!el.classList.contains('is-hidden')) hide();
        }, 3500);
    }, true);

    // Soft loader for in-page fetch (checkout / auth) via custom events
    window.addEventListener('gaget:loading', function (e) {
        show((e && e.detail && e.detail.message) || 'Working on it');
    });
    window.addEventListener('gaget:loaded', function () {
        hide();
    });
})();
</script>
@stack('scripts')
</body>
</html>
