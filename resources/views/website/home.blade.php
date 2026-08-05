@extends('website.layout')
@php $ws = app(\App\Services\WebsiteService::class); @endphp

@section('content')

{{-- Hero posters (CMS → Home Posters) — 1920×640 / 3:1 full-design art --}}
<section class="tn-hero" x-data="{ slide: 0, total: {{ max($heroSlides->count(), 1) }} }"
         @if($heroSlides->count() > 1) x-init="setInterval(()=>{ slide=(slide+1)%total }, 6000)" @endif>
    @if($heroSlides->count() > 1)
        <button type="button" @click="slide=(slide-1+total)%total" class="tn-hero-arrow left" aria-label="Previous">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="slide=(slide+1)%total" class="tn-hero-arrow right" aria-label="Next">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    @endif
    @forelse($heroSlides as $i => $slide)
        @php
            $posterUrl = $slide->image_path ? public_storage_url($slide->image_path) : null;
            if ($posterUrl && $slide->image_path) {
                $full = storage_path('app/public/'.$slide->image_path);
                $posterUrl .= '?v='.(is_file($full) ? filemtime($full) : time());
            }
            $link = $slide->button_url ?: route('website.shop');
        @endphp
        <div class="tn-hero-slide" x-show="slide==={{ $i }}" @if($i > 0) x-cloak @endif>
            @if($posterUrl)
                <a href="{{ $link }}" class="tn-hero-poster" aria-label="{{ $slide->title }}">
                    <img
                        src="{{ $posterUrl }}"
                        alt="{{ $slide->title }}"
                        class="tn-hero-img"
                        width="1920"
                        height="640"
                        decoding="async"
                        @if($i === 0) fetchpriority="high" @endif
                    >
                </a>
            @else
                <div class="tn-hero-fallback">
                    <div class="tn-container tn-hero-fallback-inner">
                        <p class="tn-hero-kicker">{{ data_get($settings, 'special_offer_text') ?: 'Premium Electronics' }}</p>
                        <h1 class="tn-hero-title">Upgrade Your Digital Life</h1>
                        <p class="tn-hero-sub">Discover the latest gadgets, unbeatable deals, and premium tech at {{ $settings->store_name ?? 'our store' }}.</p>
                        <div class="tn-hero-actions">
                            <a href="{{ route('website.shop') }}" class="tn-btn tn-btn-primary">Shop Now</a>
                            <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-btn tn-btn-outline">Explore Collection</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="tn-hero-slide">
            <div class="tn-hero-fallback">
                <div class="tn-container tn-hero-fallback-inner">
                    <p class="tn-hero-kicker">{{ data_get($settings, 'special_offer_text') ?: 'Premium Electronics' }}</p>
                    <h1 class="tn-hero-title">Upgrade Your Digital Life</h1>
                    <p class="tn-hero-sub">Discover the latest gadgets, unbeatable deals, and premium tech at {{ $settings->store_name ?? config('app.name', 'Akhi Telecom') }}.</p>
                    <div class="tn-hero-actions">
                        <a href="{{ route('website.shop') }}" class="tn-btn tn-btn-primary">Shop Now</a>
                        <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-btn tn-btn-outline">Explore Collection</a>
                    </div>
                </div>
            </div>
        </div>
    @endforelse
    @if($heroSlides->count() > 1)
        <div class="tn-hero-dots">
            @foreach($heroSlides as $di => $ds)
                <button type="button" @click="slide={{ $di }}" class="tn-hero-dot" :class="slide==={{ $di }}?'active':''" aria-label="Slide {{ $di + 1 }}"></button>
            @endforeach
        </div>
    @endif
</section>

{{-- Service features — premium strip under hero --}}
@if($features->isNotEmpty())
<section class="tn-features">
    <div class="tn-container">
        <div class="tn-features-panel">
            <div class="tn-features-grid tn-features-grid--{{ min(max($features->count(), 1), 4) }}">
                @foreach($features as $feature)
                    <div class="tn-feature">
                        <div class="tn-feature-icon">@include('website.partials.feature-icon', ['icon' => $feature->icon])</div>
                        <div class="tn-feature-copy">
                            <p class="tn-feature-title">{{ $feature->title }}</p>
                            @if($feature->subtitle)<p class="tn-feature-sub">{{ $feature->subtitle }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- Shop by Category — colorful icon strip --}}
@if($categories->isNotEmpty())
<section class="tn-section tn-section-cats">
    <div class="tn-container">
        <div class="tn-section-head">
            <h2 class="tn-section-title">Shop by Category</h2>
            <a href="{{ route('website.shop') }}" class="tn-section-link">View All Categories &rarr;</a>
        </div>
        <div class="tn-cat-grid">
            @foreach($categories as $category)
                @php $iconMeta = $category->iconMeta(); @endphp
                <a href="{{ route('website.category', $category->slug ?? $category->id) }}" class="tn-cat-card">
                    <div class="tn-cat-icon" style="--cat-bg: {{ $iconMeta['bg'] }}; --cat-color: {{ $iconMeta['color'] }};">
                        @include('website.partials.category-icon-svg', ['icon' => $iconMeta['key']])
                    </div>
                    <span class="tn-cat-name">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Flash Sale --}}
@if($flashSaleProducts->isNotEmpty())
<section class="tn-flash">
    <div class="tn-container">
        <div class="tn-flash-head">
            <div class="tn-flash-head-left">
                <div class="tn-flash-title-row">
                    <span class="tn-flash-bolt" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L4.5 13.5H11l-1 8.5L19.5 10.5H13L13 2z"/></svg>
                    </span>
                    <h2 class="tn-flash-title">Flash Sale</h2>
                    <span class="tn-flash-live">Live</span>
                </div>
                <div class="tn-countdown tn-countdown--flash" x-data="{
                    h:0,m:0,s:0,
                    end: {{ ($flashSaleEndsAt ?? null) ? ((int) $flashSaleEndsAt->timestamp * 1000) : 'null' }},
                    tick(){
                        const target = this.end ?? new Date().setHours(23,59,59,999);
                        const d = Math.max(0, target - Date.now());
                        this.h = Math.floor(d/3600000);
                        this.m = Math.floor((d%3600000)/60000);
                        this.s = Math.floor((d%60000)/1000);
                    }
                }" x-init="tick(); setInterval(()=>tick(),1000)">
                    <span class="tn-countdown-label">Ends in</span>
                    <span class="tn-countdown-box"><strong x-text="String(h).padStart(2,'0')">00</strong><small>Hrs</small></span>
                    <span class="tn-countdown-sep">:</span>
                    <span class="tn-countdown-box"><strong x-text="String(m).padStart(2,'0')">00</strong><small>Min</small></span>
                    <span class="tn-countdown-sep">:</span>
                    <span class="tn-countdown-box"><strong x-text="String(s).padStart(2,'0')">00</strong><small>Sec</small></span>
                </div>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'deals']) }}" class="tn-flash-link">View All Deals &rarr;</a>
        </div>

        <div class="tn-flash-grid">
            @foreach($flashSaleProducts as $product)
                @include('website.partials.tn-product-card', ['product' => $product, 'flash' => true])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- New Arrivals --}}
@if(($newArrivals ?? collect())->isNotEmpty())
<section class="tn-section tn-section-new">
    <div class="tn-container">
        <div class="tn-section-head">
            <div class="tn-section-head-left">
                <h2 class="tn-section-title">New Arrivals</h2>
                <span class="tn-new-pill">Just in</span>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-section-link">View All New Arrivals &rarr;</a>
        </div>
        <div class="tn-flash-grid">
            @foreach($newArrivals as $product)
                @include('website.partials.tn-product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Trending Products --}}
@if($trendingProducts->isNotEmpty())
<section class="tn-section">
    <div class="tn-container">
        <div class="tn-section-head">
            <h2 class="tn-section-title">Trending Products</h2>
            <a href="{{ route('website.shop', ['filter' => 'bestsellers']) }}" class="tn-section-link">View All Products &rarr;</a>
        </div>
        <div class="tn-product-grid">
            @foreach($trendingProducts as $product)
                @include('website.partials.tn-product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Trusted brands — mockup-identical panel, dynamic brand logos --}}
@if($brands->isNotEmpty())
<section class="tn-brands">
    <div class="tn-container">
        <div class="tn-brands-panel">
            <div class="tn-brands-head">
                <div class="tn-brands-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M9.5 12.2l1.8 1.8 3.4-3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Trusted by
                </div>
                <h2 class="tn-brands-title">Gadget Lovers Across <em>Bangladesh</em></h2>
                <p class="tn-brands-sub">We partner with the world's leading brands to bring you 100% authentic products and the best tech experience.</p>
            </div>

            <div class="tn-brands-grid">
                @foreach($brands as $brand)
                    @php
                        $brandSlug = \Illuminate\Support\Str::slug($brand->name);
                        $logoUrl = $brand->logo_url;
                    @endphp
                    <a href="{{ route('website.brand', $brandSlug) }}"
                       class="tn-brand-card"
                       title="Shop {{ $brand->name }}">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}"
                                 alt="{{ $brand->name }}"
                                 class="tn-brand-logo"
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.classList.add('is-broken'); this.nextElementSibling?.classList.add('is-visible');">
                            <span class="tn-brand-fallback">{{ $brand->name }}</span>
                        @else
                            <span class="tn-brand-fallback is-visible">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="tn-brands-foot">
                <span class="tn-brands-foot-line" aria-hidden="true"></span>
                <span class="tn-brands-foot-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6.7-4.35-9.33-8.1C.8 10.2 1.5 6.9 4.4 5.55 6.3 4.65 8.55 5.1 10 6.55L12 8.6l2-2.05c1.45-1.45 3.7-1.9 5.6-1 2.9 1.35 3.6 4.65 1.73 7.35C18.7 16.65 12 21 12 21z"/></svg>
                    Thank you for choosing us
                </span>
                <span class="tn-brands-foot-line" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Deals You'll Love (CMS → Landing Page promo banners) --}}
@if($promoBanners->isNotEmpty())
@php
    $dealsKicker = data_get($settings, 'deals_kicker') ?: 'Special Offers';
    $dealsTitle = data_get($settings, 'deals_title') ?: "Deals You'll";
    $dealsAccent = data_get($settings, 'deals_title_accent') ?: 'Love';
    $dealsSub = data_get($settings, 'deals_subtitle') ?: 'Grab the best deals on top-quality gadgets and accessories.';
@endphp
<section class="tn-deals">
    <div class="tn-container">
        <div class="tn-deals-head">
            <div class="tn-deals-kicker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                    <circle cx="7" cy="7" r="1.2" fill="currentColor" stroke="none"/>
                </svg>
                {{ $dealsKicker }}
            </div>
            <h2 class="tn-deals-title">
                <span class="tn-deals-rays tn-deals-rays--left" aria-hidden="true"><i></i><i></i><i></i></span>
                <span>{{ $dealsTitle }} <em>{{ $dealsAccent }}</em></span>
                <span class="tn-deals-rays tn-deals-rays--right" aria-hidden="true"><i></i><i></i><i></i></span>
            </h2>
            <p class="tn-deals-sub">{{ $dealsSub }}</p>
        </div>

        <div class="tn-deals-grid">
            @foreach($promoBanners->take(2) as $banner)
                @php
                    $isLight = $banner->theme === 'light';
                    $url = $banner->button_url ?: route('website.shop');
                    $sub = (string) ($banner->subtitle ?? '');
                    $hi = trim((string) ($banner->highlight_text ?? ''));
                    if ($hi !== '' && $sub !== '' && str_contains($sub, $hi)) {
                        $subHtml = str_replace($hi, '<strong>'.e($hi).'</strong>', e($sub));
                    } else {
                        $subHtml = e($sub);
                    }
                @endphp
                <article class="tn-deal {{ $isLight ? 'is-light' : 'is-dark' }}">
                    @if($banner->discount_badge)
                        <span class="tn-deal-disc">{{ $banner->discount_badge }}</span>
                    @endif

                    <div class="tn-deal-body">
                        @if($banner->badge_text)
                            <span class="tn-deal-badge">
                                @if($isLight)
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2L4 14h7l-1 8 10-14h-7l1-6z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.5 2.2c.3-.5 1.1-.3 1.1.3v5.2h4.2c.6 0 .9.7.5 1.1l-8.4 9.7c-.4.5-1.2.2-1.1-.5l.8-5.5H5.5c-.6 0-.9-.7-.5-1.1L12.5 2.2z"/></svg>
                                @endif
                                {{ $banner->badge_text }}
                            </span>
                        @endif

                        <div class="tn-deal-copy">
                            <div class="tn-deal-copy-main">
                                <h3 class="tn-deal-name">{{ $banner->title }}</h3>
                                @if($sub !== '')
                                    <p class="tn-deal-offer">{!! $subHtml !!}</p>
                                @endif
                            </div>
                            @if($banner->price_from)
                                <div class="tn-deal-price">
                                    <span>From</span>
                                    <strong>{{ $ws->formatPrice($banner->price_from, $settings) }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="tn-deal-actions">
                            <a href="{{ $url }}" class="tn-deal-cta">
                                @if($isLight)
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 7h14M10 20a1 1 0 102 0 1 1 0 00-2 0zm8 0a1 1 0 102 0 1 1 0 00-2 0z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                @endif
                                {{ $banner->button_text ?: 'Shop Now' }}
                            </a>
                            <a href="{{ $url }}" class="tn-deal-arrow" aria-label="{{ $banner->button_text ?: 'Shop Now' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="tn-deal-media">
                        <div class="tn-deal-podium" aria-hidden="true"></div>
                        @if($banner->image_path)
                            <img src="{{ public_storage_url($banner->image_path) }}" alt="{{ $banner->title }}" class="tn-deal-img">
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Testimonials (CMS → Reviews) --}}
@if(($featuredReviews ?? collect())->isNotEmpty())
<section class="tn-section">
    <div class="tn-container">
        <div class="tn-section-head tn-section-head-center">
            <h2 class="tn-section-title">What Our Customers Say</h2>
            <p class="tn-section-desc">Real feedback from shoppers who love our products and service.</p>
        </div>
        <div class="tn-review-grid">
            @foreach($featuredReviews->take(3) as $review)
                <div class="tn-review-card">
                    <div class="tn-review-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= (int) $review->rating ? 'filled' : '' }}">&#9733;</span>
                        @endfor
                    </div>
                    <p class="tn-review-body">&ldquo;{{ $review->body }}&rdquo;</p>
                    <div class="tn-review-author">
                        @if($review->avatar_path)
                            <img src="{{ public_storage_url($review->avatar_path) }}" alt="" class="tn-review-avatar">
                        @else
                            <div class="tn-review-avatar tn-review-avatar--letter">{{ strtoupper(mb_substr($review->customer_name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <p class="tn-review-name">{{ $review->customer_name }}</p>
                            @if($review->customer_title)
                                <p class="tn-review-role">{{ $review->customer_title }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Blog (CMS → Blog) --}}
@if(($latestBlogs ?? collect())->isNotEmpty())
<section class="tn-section tn-section-muted">
    <div class="tn-container">
        <div class="tn-section-head">
            <h2 class="tn-section-title">Latest from Blog</h2>
            <a href="{{ route('website.blogs') }}" class="tn-section-link">View All Posts &rarr;</a>
        </div>
        <div class="tn-blog-grid">
            @foreach($latestBlogs as $post)
                <a href="{{ route('website.blog', $post->slug) }}" class="tn-blog-card">
                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="tn-blog-img">
                    <div class="tn-blog-body">
                        @if($post->category)
                            <span class="tn-blog-tag">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="tn-blog-title">{{ $post->title }}</h3>
                        <p class="tn-blog-meta">
                            {{ optional($post->published_at)->format('M d, Y') }}
                            &middot; {{ $post->readingTimeLabel() }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
