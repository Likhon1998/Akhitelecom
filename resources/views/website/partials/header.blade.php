<div class="gaget-sticky-header">
    {{-- Main header --}}
    <div class="gaget-header-main">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 py-3.5">
            <div class="gaget-header-row">
                <div class="gaget-header-left">
                <button type="button"
                        class="gaget-mobile-menu-btn md:hidden"
                        @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen"
                        aria-label="Menu">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 no-underline min-w-0">
                    @if($settings->logo_path ?? false)
                        <img src="{{ public_storage_url($settings->logo_path) }}" alt="" class="gaget-logo-icon object-contain p-1">
                    @else
                        <div class="gaget-logo-icon">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                    @endif
                    <span class="gaget-logo-text truncate">{{ $settings->store_name ?? 'GAGET STORE' }}</span>
                </a>
                </div>

                {{-- Live search: truly centered between logo and actions --}}
                <div class="gaget-header-center hidden lg:block"
                     x-data="headerSearch(@js(route('website.search.suggest')), @js(route('website.shop')), @js($settings->currency_symbol ?? '$'))"
                     @click.outside="open = false">
                    <form action="{{ route('website.shop') }}" method="GET" class="gaget-search-wrap" @submit="open = false">
                        <div class="gaget-search-bar">
                            <input type="search"
                                   name="search"
                                   x-model="q"
                                   value="{{ request('search') }}"
                                   placeholder="Search for products, brands..."
                                   class="gaget-search-input"
                                   autocomplete="off"
                                   @focus="onFocus()"
                                   @input.debounce.200ms="fetchSuggestions()"
                                   @keydown.escape.prevent="open = false"
                                   @keydown.arrow-down.prevent="move(1)"
                                   @keydown.arrow-up.prevent="move(-1)"
                                   @keydown.enter="onEnter($event)">
                            <select name="category" class="gaget-search-select" x-model="category" @change="fetchSuggestions()">
                                <option value="">All Categories</option>
                                @foreach($allCategories ?? $categories ?? [] as $cat)
                                    <option value="{{ $cat->slug }}" {{ request('category')==$cat->slug?'selected':'' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="gaget-search-btn" aria-label="Search">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </div>

                        <div class="gaget-search-suggest" x-show="open" x-cloak x-transition.opacity.duration.120ms>
                            <div class="gaget-search-suggest__head">
                                <span x-text="mode === 'best' ? 'Best picks for you' : 'Matching products'"></span>
                                <span class="gaget-search-suggest__hint" x-show="loading">Searching…</span>
                            </div>

                            <template x-if="!loading && products.length === 0">
                                <p class="gaget-search-suggest__empty" x-text="q ? 'No products found' : 'No products available yet'"></p>
                            </template>

                            <div class="gaget-search-suggest__list" x-show="products.length > 0">
                                <template x-for="(item, index) in products" :key="item.id">
                                    <a :href="item.url"
                                       class="gaget-search-suggest__item"
                                       :class="{ 'is-active': index === activeIndex }"
                                       @mouseenter="activeIndex = index">
                                        <img :src="item.image" :alt="item.name" loading="lazy">
                                        <div class="min-w-0 flex-1">
                                            <p class="gaget-search-suggest__name" x-text="item.name"></p>
                                            <p class="gaget-search-suggest__meta" x-text="item.brand || 'Product'"></p>
                                        </div>
                                        <strong class="gaget-search-suggest__price" x-text="currency + Number(item.price).toFixed(2)"></strong>
                                    </a>
                                </template>
                            </div>

                            <a :href="shopUrl" class="gaget-search-suggest__all" x-show="q.length > 0">
                                View all results for “<span x-text="q"></span>”
                            </a>
                        </div>
                    </form>
                </div>

                <div class="gaget-header-right flex items-center gap-0.5">
                    {{-- Wishlist with hover preview --}}
                    <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                        <a href="{{ route('website.wishlist') }}" class="gaget-action-btn" aria-label="Wishlist">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            <span class="gaget-action-label">Wishlist</span>
                            <span x-show="wishlistCount>0" x-text="wishlistCount" class="gaget-cart-badge" x-cloak></span>
                        </a>
                        <div class="gaget-hover-panel" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                            <div class="gaget-hover-panel__head">
                                <span>Wishlist</span>
                                <strong x-text="wishlistCount + (wishlistCount === 1 ? ' item' : ' items')"></strong>
                            </div>
                            <template x-if="wishlist.length === 0">
                                <p class="gaget-hover-panel__empty">No saved items yet.</p>
                            </template>
                            <div class="gaget-hover-panel__list" x-show="wishlist.length > 0">
                                <template x-for="item in wishlist.slice(0, 4)" :key="'w'+item.id">
                                    <a :href="item.url" class="gaget-hover-panel__row">
                                        <img :src="item.image" :alt="item.name">
                                        <div>
                                            <p x-text="item.name"></p>
                                            <span x-text="currency + Number(item.price).toFixed(2)"></span>
                                        </div>
                                    </a>
                                </template>
                            </div>
                            <a href="{{ route('website.wishlist') }}" class="gaget-hover-panel__cta" x-show="wishlistCount > 0">View wishlist</a>
                        </div>
                    </div>

                    {{-- Cart with hover preview --}}
                    <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                        <button type="button" @click="cartOpen=true" class="gaget-action-btn" aria-label="Cart">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="gaget-action-label">Cart</span>
                            <span class="gaget-cart-badge" x-text="cartCount" x-show="cartCount > 0" x-cloak></span>
                        </button>
                        <div class="gaget-hover-panel gaget-hover-panel--cart" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                            <div class="gaget-hover-panel__head">
                                <span>Cart</span>
                                <strong x-text="cartCount + (cartCount === 1 ? ' item' : ' items')"></strong>
                            </div>
                            <template x-if="cart.length === 0">
                                <p class="gaget-hover-panel__empty">Your cart is empty.</p>
                            </template>
                            <div class="gaget-hover-panel__list" x-show="cart.length > 0">
                                <template x-for="item in cart.slice(0, 4)" :key="'c'+item.id">
                                    <div class="gaget-hover-panel__row">
                                        <img :src="item.image" :alt="item.name">
                                        <div>
                                            <p x-text="item.name"></p>
                                            <span x-text="item.qty + ' × ' + currency + Number(item.price).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="gaget-hover-panel__foot" x-show="cart.length > 0">
                                <div class="gaget-hover-panel__total">
                                    <span>Total</span>
                                    <strong x-text="currency + cartTotal.toFixed(2)"></strong>
                                </div>
                                <button type="button" class="gaget-hover-panel__cta" @click="open=false; cartOpen=true">View cart</button>
                            </div>
                        </div>
                    </div>

                    @auth
                        @if(auth()->user()->isStorefrontCustomer())
                            <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                                <a href="{{ route('website.account') }}" class="gaget-action-btn" title="{{ auth()->user()->name }}">
                                    @if(auth()->user()->avatarUrl())
                                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-7 w-7 rounded-full object-cover border border-slate-200">
                                    @else
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-700">{{ auth()->user()->avatarInitials() }}</span>
                                    @endif
                                    <span class="hidden xl:inline text-xs font-semibold ml-1 max-w-[72px] truncate">{{ explode(' ', auth()->user()->name)[0] }}</span>
                                </a>
                                <div class="gaget-hover-panel gaget-hover-panel--account" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                    <div class="gaget-account-menu__user">
                                        @if(auth()->user()->avatarUrl())
                                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="gaget-account-menu__avatar">
                                        @else
                                            <span class="gaget-account-menu__avatar gaget-account-menu__avatar--initials">{{ auth()->user()->avatarInitials() }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="gaget-account-menu__name">{{ auth()->user()->name }}</p>
                                            <p class="gaget-account-menu__email">{{ auth()->user()->email }}</p>
                                        </div>
                                    </div>
                                    <div class="gaget-account-menu__links">
                                        <a href="{{ route('website.account') }}" class="gaget-account-menu__item">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            Profile
                                        </a>
                                        <button type="button"
                                                class="gaget-account-menu__item gaget-account-menu__item--danger"
                                                @mousedown.prevent="typeof storefrontLogout === 'function' && storefrontLogout()">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Sign out
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('dashboard') }}" class="gaget-action-btn" title="Staff account">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </a>
                        @endif
                    @else
                        <div class="gaget-action-wrap" x-data="{ open: false }" @mouseenter="open=true" @mouseleave="open=false">
                            <button type="button" @click="openSignIn('login')" class="gaget-action-btn" title="Sign in">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="hidden xl:inline text-xs font-semibold ml-1">Account</span>
                            </button>
                            <div class="gaget-hover-panel gaget-hover-panel--account" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                <div class="gaget-account-menu__links">
                                    <button type="button" @click="open=false; openSignIn('login')" class="gaget-account-menu__item w-full text-left">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                        Sign in
                                    </button>
                                    <button type="button" @click="open=false; openSignIn('register')" class="gaget-account-menu__item w-full text-left">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        Create account
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>

            {{-- Mobile live search --}}
            <div class="lg:hidden mt-3"
                 x-data="headerSearch(@js(route('website.search.suggest')), @js(route('website.shop')), @js($settings->currency_symbol ?? '$'))"
                 @click.outside="open = false">
                <form action="{{ route('website.shop') }}" method="GET" class="gaget-search-wrap" @submit="open = false">
                    <div class="gaget-mobile-search">
                        <input type="search"
                               name="search"
                               x-model="q"
                               value="{{ request('search') }}"
                               placeholder="Search products..."
                               class="gaget-mobile-search-input"
                               autocomplete="off"
                               @focus="onFocus()"
                               @input.debounce.200ms="fetchSuggestions()"
                               @keydown.escape.prevent="open = false"
                               @keydown.arrow-down.prevent="move(1)"
                               @keydown.arrow-up.prevent="move(-1)"
                               @keydown.enter="onEnter($event)">
                        <button type="submit" aria-label="Search" class="gaget-mobile-search-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>

                    <div class="gaget-search-suggest" x-show="open" x-cloak x-transition.opacity.duration.120ms>
                        <div class="gaget-search-suggest__head">
                            <span x-text="mode === 'best' ? 'Best picks for you' : 'Matching products'"></span>
                            <span class="gaget-search-suggest__hint" x-show="loading">Searching…</span>
                        </div>
                        <template x-if="!loading && products.length === 0">
                            <p class="gaget-search-suggest__empty" x-text="q ? 'No products found' : 'No products available yet'"></p>
                        </template>
                        <div class="gaget-search-suggest__list" x-show="products.length > 0">
                            <template x-for="(item, index) in products" :key="'m'+item.id">
                                <a :href="item.url"
                                   class="gaget-search-suggest__item"
                                   :class="{ 'is-active': index === activeIndex }"
                                   @mouseenter="activeIndex = index">
                                    <img :src="item.image" :alt="item.name" loading="lazy">
                                    <div class="min-w-0 flex-1">
                                        <p class="gaget-search-suggest__name" x-text="item.name"></p>
                                        <p class="gaget-search-suggest__meta" x-text="item.brand || 'Product'"></p>
                                    </div>
                                    <strong class="gaget-search-suggest__price" x-text="currency + Number(item.price).toFixed(2)"></strong>
                                </a>
                            </template>
                        </div>
                        <a :href="shopUrl" class="gaget-search-suggest__all" x-show="q.length > 0">
                            View all results for “<span x-text="q"></span>”
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Mobile nav drawer --}}
    <div x-show="mobileOpen" x-cloak class="gaget-mobile-drawer md:hidden" @click.self="mobileOpen = false">
        <div class="gaget-mobile-drawer-panel" x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <p class="gaget-mobile-drawer-title">Menu</p>
            <a href="{{ route('home') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">Home</a>
            <a href="{{ route('website.shop') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">Shop</a>
            <a href="{{ route('website.shop', ['filter'=>'deals']) }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">Deals</a>
            <a href="{{ route('website.shop', ['filter'=>'new']) }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">New Arrivals</a>
            <a href="{{ route('website.blogs') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">Blog</a>
            <a href="{{ route('website.faqs') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">FAQ</a>
            <a href="{{ route('website.contact') }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">Contact</a>
            @if(($allCategories ?? collect())->isNotEmpty())
                <p class="gaget-mobile-drawer-title mt-4">Categories</p>
                @foreach(($allCategories ?? []) as $cat)
                    <a href="{{ route('website.category', $cat->slug) }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ $cat->name }}</a>
                @endforeach
            @endif
            @if(($brands ?? collect())->isNotEmpty())
                <p class="gaget-mobile-drawer-title mt-4">Brands</p>
                @foreach(($brands ?? []) as $brand)
                    <a href="{{ route('website.brand', \Illuminate\Support\Str::slug($brand->name)) }}" class="gaget-mobile-drawer-link" @click="mobileOpen = false">{{ $brand->name }}</a>
                @endforeach
            @endif
            @auth
                @if(auth()->user()->isStorefrontCustomer())
                    <a href="{{ route('website.account') }}" class="gaget-mobile-drawer-link mt-4" @click="mobileOpen = false">My Account</a>
                    <button type="button"
                            class="gaget-mobile-drawer-link w-full text-left text-rose-300"
                            @click="mobileOpen = false; typeof storefrontLogout === 'function' && storefrontLogout()">
                        Sign out
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="gaget-mobile-drawer-link mt-4" @click="mobileOpen = false">Staff dashboard</a>
                @endif
            @else
                <button type="button" class="gaget-mobile-drawer-link mt-4 w-full text-left" @click="mobileOpen = false; openSignIn('login')">Sign in</button>
            @endauth
        </div>
    </div>

    {{-- Full-width navy nav --}}
    <nav class="gaget-navbar hidden md:block">
        <div class="gaget-navbar-inner">
            <div class="gaget-navbar-left">
                <div class="gaget-nav-links">
                    @php
                        $navLinks = $mainNav ?? collect();
                    @endphp
                    @forelse($navLinks as $link)
                        @php $navLabel = trim((string) $link->label); @endphp
                        @if(strcasecmp($navLabel, 'Categories') === 0 || strcasecmp($navLabel, 'Category') === 0)
                            <div class="gaget-nav-dropdown">
                                <a href="{{ $link->url ?: route('website.shop') }}" class="gaget-nav-link gaget-nav-link--dropdown">
                                    {{ $link->label }}
                                    <svg class="gaget-nav-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </a>
                                <div class="gaget-nav-dropdown-menu">
                                    @forelse($allCategories ?? [] as $cat)
                                        <a href="{{ route('website.category', $cat->slug) }}" class="gaget-nav-dropdown-item">
                                            <span>{{ $cat->name }}</span>
                                            <span class="gaget-nav-dropdown-count">{{ $cat->products_count ?? 0 }}</span>
                                        </a>
                                    @empty
                                        <span class="gaget-nav-dropdown-empty">No categories yet</span>
                                    @endforelse
                                </div>
                            </div>
                        @elseif(strcasecmp($navLabel, 'Brands') === 0 || strcasecmp($navLabel, 'Brand') === 0)
                            <div class="gaget-nav-dropdown">
                                <a href="{{ $link->url ?: route('home').'#brands' }}" class="gaget-nav-link gaget-nav-link--dropdown">
                                    {{ $link->label }}
                                    <svg class="gaget-nav-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </a>
                                <div class="gaget-nav-dropdown-menu">
                                    @forelse($brands ?? [] as $brand)
                                        <a href="{{ route('website.brand', \Illuminate\Support\Str::slug($brand->name)) }}" class="gaget-nav-dropdown-item">
                                            <span>{{ $brand->name }}</span>
                                            <span class="gaget-nav-dropdown-count">{{ $brand->products_count ?? 0 }}</span>
                                        </a>
                                    @empty
                                        <span class="gaget-nav-dropdown-empty">No brands yet</span>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <a href="{{ $link->url }}" class="gaget-nav-link">{{ $link->label }}</a>
                        @endif
                    @empty
                        <a href="{{ route('home') }}" class="gaget-nav-link">Home</a>
                        <a href="{{ route('website.shop') }}" class="gaget-nav-link">Shop</a>

                        <div class="gaget-nav-dropdown">
                            <a href="{{ route('website.shop') }}" class="gaget-nav-link gaget-nav-link--dropdown">
                                Categories
                                <svg class="gaget-nav-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                            <div class="gaget-nav-dropdown-menu">
                                @forelse($allCategories ?? [] as $cat)
                                    <a href="{{ route('website.category', $cat->slug) }}" class="gaget-nav-dropdown-item">
                                        <span>{{ $cat->name }}</span>
                                        <span class="gaget-nav-dropdown-count">{{ $cat->products_count ?? 0 }}</span>
                                    </a>
                                @empty
                                    <span class="gaget-nav-dropdown-empty">No categories yet</span>
                                @endforelse
                            </div>
                        </div>

                        <a href="{{ route('website.shop', ['filter'=>'deals']) }}" class="gaget-nav-link">Deals</a>
                        <a href="{{ route('website.shop', ['filter'=>'new']) }}" class="gaget-nav-link">New Arrivals</a>
                        <a href="{{ route('website.blogs') }}" class="gaget-nav-link">Blog</a>
                        <a href="{{ route('website.faqs') }}" class="gaget-nav-link">FAQ</a>
                        <a href="{{ route('website.contact') }}" class="gaget-nav-link {{ request()->routeIs('website.contact') ? 'text-blue-300' : '' }}">Contact</a>

                        <div class="gaget-nav-dropdown">
                            <a href="{{ route('home') }}#brands" class="gaget-nav-link gaget-nav-link--dropdown">
                                Brands
                                <svg class="gaget-nav-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                            <div class="gaget-nav-dropdown-menu">
                                @forelse($brands ?? [] as $brand)
                                    <a href="{{ route('website.brand', \Illuminate\Support\Str::slug($brand->name)) }}" class="gaget-nav-dropdown-item">
                                        <span>{{ $brand->name }}</span>
                                        <span class="gaget-nav-dropdown-count">{{ $brand->products_count ?? 0 }}</span>
                                    </a>
                                @empty
                                    <span class="gaget-nav-dropdown-empty">No brands yet</span>
                                @endforelse
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
            <a href="{{ route('website.shop', ['filter'=>'deals']) }}" class="gaget-special-offer">🔥 {{ $settings->special_offer_text ?? 'Special Offer!' }}</a>
        </div>
    </nav>
</div>
