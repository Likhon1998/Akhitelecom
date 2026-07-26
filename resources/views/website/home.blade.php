@extends('website.layout')
@php $ws = app(\App\Services\WebsiteService::class); @endphp

@section('content')

{{-- Hero posters (CMS → Home Posters) — 1600×400 full-design art --}}
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
            $link = $slide->button_url ?: route('website.shop');
        @endphp
        <div class="tn-hero-slide" x-show="slide==={{ $i }}" @if($i > 0) x-cloak @endif>
            @if($posterUrl)
                <a href="{{ $link }}" class="tn-hero-poster" aria-label="{{ $slide->title }}">
                    <img
                        src="{{ $posterUrl }}"
                        alt="{{ $slide->title }}"
                        class="tn-hero-img"
                        width="1600"
                        height="400"
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
                    <p class="tn-hero-sub">Add poster banners in CMS &rarr; Home Posters to customize this section.</p>
                    <a href="{{ route('website.shop') }}" class="tn-btn tn-btn-primary">Shop Now</a>
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

{{-- Service features — flush under banner, 5 across --}}
@if($features->isNotEmpty())
<section class="tn-features">
    <div class="tn-container">
        <div class="tn-features-grid tn-features-grid--{{ min($features->count(), 5) }}">
            @foreach($features as $feature)
                <div class="tn-feature">
                    <div class="tn-feature-icon">@include('website.partials.feature-icon', ['icon' => $feature->icon])</div>
                    <div>
                        <p class="tn-feature-title">{{ $feature->title }}</p>
                        @if($feature->subtitle)<p class="tn-feature-sub">{{ $feature->subtitle }}</p>@endif
                    </div>
                </div>
            @endforeach
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
                    tick(){ const d=Math.max(0,new Date().setHours(23,59,59,999)-Date.now()); this.h=Math.floor(d/3600000); this.m=Math.floor((d%3600000)/60000); this.s=Math.floor((d%60000)/1000); }
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

{{-- Brands --}}
@if($brands->isNotEmpty())
<section class="tn-brands">
    <div class="tn-container">
        <p class="tn-brands-label">{{ $settings->trusted_by_text ?? 'Trusted by leading brands worldwide' }}</p>
        <div class="tn-brands-row">
            @foreach($brands as $brand)
                <a href="{{ route('website.brand', \Illuminate\Support\Str::slug($brand->name)) }}" class="tn-brand-item">
                    {{ $brand->name }}
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Promo banners (CMS → Landing Page) --}}
@if($promoBanners->isNotEmpty())
<section class="tn-section">
    <div class="tn-container">
        <div class="tn-promo-grid">
            @foreach($promoBanners->take(2) as $banner)
                <div class="tn-promo {{ $banner->theme === 'light' ? 'tn-promo-light' : 'tn-promo-dark' }}">
                    <div class="tn-promo-body">
                        <h3 class="tn-promo-title">{{ $banner->title }}</h3>
                        @if($banner->subtitle)<p class="tn-promo-sub">{{ $banner->subtitle }}</p>@endif
                        @if($banner->price_from)
                            <p class="tn-promo-price">From <strong>{{ $ws->formatPrice($banner->price_from, $settings) }}</strong></p>
                        @endif
                        <a href="{{ $banner->button_url ?? route('website.shop') }}" class="tn-btn {{ $banner->theme === 'light' ? 'tn-btn-link' : 'tn-btn-primary tn-btn-sm' }}">
                            {{ $banner->button_text ?? 'Shop Now' }}
                        </a>
                    </div>
                    @if($banner->image_path)
                        <img src="{{ public_storage_url($banner->image_path) }}" alt="{{ $banner->title }}" class="tn-promo-img">
                    @endif
                </div>
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
                            @if($post->viewsLabel()) &middot; {{ $post->viewsLabel() }} @endif
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
