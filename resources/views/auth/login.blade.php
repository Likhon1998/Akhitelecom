<x-login-layout>
    @php
        $brandName = data_get($settings ?? null, 'store_name') ?: config('app.name', 'Akhi Telecom');
        $brandLogo = !empty(data_get($settings ?? null, 'logo_path'))
            ? public_storage_url($settings->logo_path)
            : null;
        $brandIcon = !empty(data_get($settings ?? null, 'favicon_path'))
            ? public_storage_url($settings->favicon_path)
            : ($brandLogo ?: null);
    @endphp
    <div class="nexa-shell">
        <section class="nexa-left" aria-label="{{ $brandName }} overview">
            <div class="nexa-left-poster" aria-hidden="true">
                <img
                    src="{{ asset('images/auth/login-pos-bg.png') }}?v=3"
                    alt=""
                    class="nexa-left-poster-img"
                    width="1600"
                    height="2400"
                    decoding="async"
                    fetchpriority="high"
                >
                <div class="nexa-left-scrim"></div>
            </div>

            <div class="nexa-left-content">
                <div class="nexa-brand">
                    @if($brandIcon)
                        <div class="nexa-brand-mark" aria-hidden="true" style="background:transparent;border:0;box-shadow:none;width:56px;height:56px;">
                            <img src="{{ $brandIcon }}?v={{ @filemtime(public_storage_path($settings->favicon_path)) ?: time() }}" alt="" style="width:56px;height:56px;object-fit:contain;">
                        </div>
                        <div>
                            <div class="nexa-brand-name">{{ strtoupper($brandName) }}</div>
                            <div class="nexa-brand-sub">Smart POS &amp; Inventory System</div>
                        </div>
                    @else
                        <div class="nexa-brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 32 32" fill="none">
                                <defs>
                                    <linearGradient id="nexaCube" x1="4" y1="4" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#60a5fa"/>
                                        <stop offset="1" stop-color="#2563eb"/>
                                    </linearGradient>
                                </defs>
                                <path d="M16 3.5L27 9.5V22.5L16 28.5L5 22.5V9.5L16 3.5Z" stroke="url(#nexaCube)" stroke-width="1.6" fill="rgba(37,99,235,.2)"/>
                                <path d="M16 15.5L27 9.5M16 15.5V28.5M16 15.5L5 9.5" stroke="url(#nexaCube)" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="nexa-brand-name">{{ strtoupper($brandName) }}</div>
                            <div class="nexa-brand-sub">Smart POS &amp; Inventory System</div>
                        </div>
                    @endif
                </div>

                <div class="nexa-left-body">
                    <h1 class="nexa-headline">
                        Manage Smarter.<br>
                        <em>Sell Faster.</em>
                    </h1>
                    <p class="nexa-lead">A modern POS and inventory solution built for today's retail businesses.</p>

                    <div class="nexa-features">
                        <div class="nexa-feature">
                            <div class="nexa-feature-icon is-purple" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3v18h18M7 14l3-3 3 2 5-6"/></svg>
                            </div>
                            <div>
                                <strong>Real-time Analytics</strong>
                                <span>Track sales and performance in real time.</span>
                            </div>
                        </div>
                        <div class="nexa-feature">
                            <div class="nexa-feature-icon is-blue" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 7h14M10 20a1 1 0 102 0 1 1 0 00-2 0zm8 0a1 1 0 102 0 1 1 0 00-2 0z"/></svg>
                            </div>
                            <div>
                                <strong>Inventory Control</strong>
                                <span>Manage stock with accuracy and ease.</span>
                            </div>
                        </div>
                        <div class="nexa-feature">
                            <div class="nexa-feature-icon is-green" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12.2l1.8 1.8 3.4-3.6"/></svg>
                            </div>
                            <div>
                                <strong>Secure &amp; Reliable</strong>
                                <span>Your data is safe with enterprise grade security.</span>
                            </div>
                        </div>
                        <div class="nexa-feature">
                            <div class="nexa-feature-icon is-orange" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 2L4 14h7l-1 8 10-14h-7l1-6z"/></svg>
                            </div>
                            <div>
                                <strong>Fast &amp; Intuitive</strong>
                                <span>Streamlined workflows for maximum productivity.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nexa-left-foot">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Trusted by customers across Bangladesh
                </div>
            </div>
        </section>

        <section class="nexa-right" aria-label="Admin sign in">
            <div class="nexa-card">
                <div class="nexa-lock" aria-hidden="true">
                    @if($brandIcon)
                        <img src="{{ $brandIcon }}" alt="" style="width:42px;height:42px;object-fit:contain;border-radius:10px;">
                    @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    @endif
                </div>
                <h2 class="nexa-card-title">Welcome Back</h2>
                <p class="nexa-card-sub">Sign in to your {{ $brandName }} admin account</p>

                @if ($errors->has('email') || $errors->has('password'))
                    <div class="nexa-alert" role="alert">
                        <strong>Access Denied</strong>
                        {{ $errors->first('email') ?: $errors->first('password') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="nexa-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="nexa-form">
                    @csrf

                    <div>
                        <label for="email" class="nexa-label">Email Address</label>
                        <div class="nexa-field">
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email"
                            >
                            <svg class="nexa-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="nexa-label-row">
                            <label for="password" class="nexa-label">Password</label>
                            @if (Route::has('password.request'))
                                <a class="nexa-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>
                        <div class="nexa-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            >
                            <svg class="nexa-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>

                    <label class="nexa-check" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        Remember me
                    </label>

                    <button type="submit" class="nexa-submit">
                        Sign In
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </button>
                </form>
            </div>

            <p class="nexa-right-foot">&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
        </section>
    </div>
</x-login-layout>
