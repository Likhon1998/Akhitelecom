@php
    $ws = app(\App\Services\WebsiteService::class);
    $images = $ws->productImageUrls($product);
    $img = $images[0];
    $reviews = $reviews ?? collect();
    $variantOptions = $variantOptions ?? ['colors' => [], 'storages' => [], 'rams' => []];
    $discountPct = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $displayName = $product->variant_group
        ? trim(preg_replace('/\s*[—\-–].*$/u', '', $product->name))
        : $product->name;
    if ($displayName === '') {
        $displayName = $product->name;
    }
@endphp

{{-- Breadcrumbs --}}
<nav class="text-xs text-slate-400 mb-4 flex flex-wrap items-center gap-1">
    <a href="{{ route('home') }}">Home</a>
    <span>/</span>
    @if($product->category)
        <a href="{{ route('website.shop', ['category' => $product->category_id]) }}">{{ $product->category->name }}</a>
        <span>/</span>
    @endif
    @if($product->brand_name || $product->brand)
        <span class="text-slate-500">{{ $product->brand_name ?? $product->brand?->name }}</span>
        <span>/</span>
    @endif
    <span class="text-slate-600">{{ $displayName }}</span>
</nav>

<div class="grid lg:grid-cols-12 gap-6 lg:gap-8"
     x-data="{ active: 0, images: @js($images), tab: 'description', qty: 1 }">

    {{-- Gallery column --}}
    <div class="lg:col-span-5 flex flex-col-reverse sm:flex-row gap-3">
        @if(count($images) > 1)
            <div class="flex sm:flex-col gap-2 w-full sm:w-14 shrink-0 overflow-x-auto sm:overflow-visible pb-1 sm:pb-0">
                @foreach($images as $i => $url)
                    <button type="button"
                            @click="active = {{ $i }}"
                            :class="active === {{ $i }} ? 'border-blue-600 ring-1 ring-blue-200' : 'border-slate-200 hover:border-slate-300'"
                            class="aspect-square w-14 sm:w-auto shrink-0 rounded-lg border bg-white overflow-hidden transition">
                        <img src="{{ $url }}" alt="" class="w-full h-full object-contain bg-white">
                    </button>
                @endforeach
            </div>
        @endif
        <div class="flex-1 relative bg-white rounded-xl border border-slate-100 overflow-hidden min-h-[260px] sm:min-h-[380px] aspect-square sm:aspect-auto">
            @if($discountPct > 0)
                <span class="absolute top-3 left-3 z-10 text-[10px] font-semibold bg-red-500 text-white px-2 py-0.5 rounded">-{{ $discountPct }}%</span>
            @endif
            <img :src="images[active]" alt="{{ $product->name }}" class="absolute inset-0 w-full h-full object-contain bg-white p-2 sm:p-3">
        </div>
    </div>

    {{-- Product info --}}
    <div class="lg:col-span-4">
        <div class="flex flex-wrap items-center gap-2 mb-2">
            @if($product->showsAsNew())
                <span class="text-[10px] font-semibold uppercase bg-emerald-500 text-white px-2 py-0.5 rounded">New</span>
            @endif
            @if($product->brand?->logo_path)
                <img src="{{ public_storage_url($product->brand->logo_path) }}"
                     alt="{{ $product->brand->name }}"
                     class="h-5 w-auto max-w-[72px] object-contain">
            @endif
            @if($product->brand_name || $product->brand)
                <span class="text-xs font-medium text-blue-600">{{ $product->brand_name ?? $product->brand?->name }}</span>
            @endif
        </div>

        <h1 class="text-lg sm:text-xl font-semibold text-slate-900 leading-snug mb-2">{{ $displayName }}</h1>

        @if($product->rating > 0 || $reviews->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs text-slate-500">
                @php $stars = $product->rating > 0 ? round($product->rating) : (int) round($reviews->avg('rating')); @endphp
                <span class="flex text-amber-400">
                    @for($i=1;$i<=5;$i++)<span class="{{ $i<=$stars?'':'text-slate-200' }}">★</span>@endfor
                </span>
                <span>({{ $product->review_count ?: $reviews->count() }} Reviews)</span>
            </div>
        @endif

        <div class="flex flex-wrap items-baseline gap-2 mb-4 pb-4 border-b border-slate-100">
            <span class="text-lg font-semibold text-blue-600">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
            @if($compareAt)
                <span class="text-sm text-slate-400 line-through">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                @if($discountPct > 0)
                    <span class="text-[10px] font-medium text-red-600 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded">{{ $discountPct }}% OFF</span>
                @endif
            @endif
        </div>

        {{-- Storage variants --}}
        @if(count($variantOptions['storages'] ?? []) > 0)
            <div class="mb-4">
                <p class="text-xs font-medium text-slate-700 mb-2">
                    Storage: <span class="text-slate-500">{{ normalize_memory_size($product->storage) ?? $product->storage ?? '—' }}</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($variantOptions['storages'] as $opt)
                        <a href="{{ $opt['url'] }}"
                           data-product-variant
                           data-no-loader
                           class="px-3 py-1.5 rounded-lg border text-xs font-medium transition
                                  {{ $opt['active'] ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-slate-200 text-slate-600 hover:border-slate-300 bg-white' }}">
                            {{ $opt['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @elseif($product->storage)
            <p class="text-xs text-slate-600 mb-4">Storage: <span class="font-medium">{{ normalize_memory_size($product->storage) ?? $product->storage }}</span></p>
        @endif

        {{-- RAM variants --}}
        @if(count($variantOptions['rams'] ?? []) > 0)
            <div class="mb-4">
                <p class="text-xs font-medium text-slate-700 mb-2">
                    RAM: <span class="text-slate-500">{{ normalize_memory_size($product->ram) ?? $product->ram ?? '—' }}</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($variantOptions['rams'] as $opt)
                        <a href="{{ $opt['url'] }}"
                           data-product-variant
                           data-no-loader
                           class="px-3 py-1.5 rounded-lg border text-xs font-medium transition
                                  {{ $opt['active'] ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-slate-200 text-slate-600 hover:border-slate-300 bg-white' }}">
                            {{ $opt['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @elseif($product->ram)
            <p class="text-xs text-slate-600 mb-4">RAM: <span class="font-medium">{{ normalize_memory_size($product->ram) ?? $product->ram }}</span></p>
        @endif

        {{-- Color variants --}}
        @if(count($variantOptions['colors'] ?? []) > 0)
            <div class="mb-4">
                <p class="text-xs font-medium text-slate-700 mb-2">
                    Color: <span class="text-slate-500">{{ $product->color ?? '—' }}</span>
                </p>
                <div class="flex flex-wrap gap-2.5">
                    @foreach($variantOptions['colors'] as $opt)
                        <a href="{{ $opt['url'] }}"
                           data-product-variant
                           data-no-loader
                           title="{{ $opt['label'] }}"
                           class="w-8 h-8 rounded-full border-2 transition shrink-0
                                  {{ $opt['active'] ? 'border-blue-600 ring-2 ring-blue-100 ring-offset-1' : 'border-slate-200 hover:border-slate-400' }}"
                           style="background-color: {{ $opt['hex'] }}; {{ in_array(strtolower($opt['hex']), ['#f8fafc', '#ffffff', '#e8e6e3', '#d4cfc8']) ? 'box-shadow: inset 0 0 0 1px rgba(0,0,0,.08)' : '' }}">
                        </a>
                    @endforeach
                </div>
            </div>
        @elseif($product->color)
            <p class="text-xs text-slate-600 mb-4">Color: <span class="font-medium">{{ $product->color }}</span></p>
        @endif

        <div class="mb-4">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <div class="inline-flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white h-9">
                    <button type="button" @click="qty = Math.max(1, qty - 1)" class="w-8 h-full text-sm text-slate-500 hover:bg-slate-50">−</button>
                    <span class="w-8 text-center text-sm font-medium text-slate-800" x-text="qty"></span>
                    <button type="button" @click="qty = Math.min({{ max(1, (int) $product->stock_quantity) }}, qty + 1)" class="w-8 h-full text-sm text-slate-500 hover:bg-slate-50">+</button>
                </div>

                @if($product->stock_quantity > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        In Stock · Ships today
                    </span>
                @else
                    <span class="text-xs font-medium text-red-600">Out of Stock</span>
                @endif
            </div>

            @if($product->stock_quantity > 0)
                @php
                    $cartItem = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $currentPrice,
                        'image' => $img,
                    ];
                @endphp
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="1"
                            class="flex-1 h-10 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Add to Cart
                    </button>
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="0"
                            data-checkout="1"
                            class="flex-1 h-10 border border-slate-300 text-slate-800 rounded-lg text-sm font-medium hover:bg-slate-50 transition">
                        Buy Now
                    </button>
                </div>
            @endif
            @php
                $listItem = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $currentPrice,
                    'image' => $img,
                    'url' => route('website.product', $product),
                    'category' => $product->category?->name ?? $product->brand_name ?? 'Electronics',
                    'rating' => (float) ($product->rating ?? 0),
                ];
            @endphp
            <div class="flex gap-2 mt-2">
                <button type="button"
                        class="flex-1 h-9 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:border-rose-200 hover:text-rose-600 inline-flex items-center justify-center gap-1.5"
                        :class="inWishlist({{ $product->id }}) && '!border-rose-200 !text-rose-600'"
                        @click="toggleWishlist(@js($listItem))">
                    <svg class="w-4 h-4" :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span x-text="inWishlist({{ $product->id }}) ? 'Wishlisted' : 'Wishlist'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-3 space-y-4">
        <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 space-y-3">
            @foreach([
                ['Fast Delivery', 'Cash on delivery available'],
                ['30-Day Returns', 'Easy return policy'],
                ['1 Year Warranty', 'Manufacturer warranty'],
                ['Secure Checkout', 'Sign in required · COD'],
            ] as [$title, $sub])
                <div class="flex gap-3 items-start">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-800">{{ $title }}</p>
                        <p class="text-[11px] text-slate-500">{{ $sub }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if($related->count())
            <div class="rounded-xl border border-slate-100 p-4">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">You may also like</h3>
                <div class="space-y-3">
                    @foreach($related->take(3) as $rel)
                        @php $relImg = $ws->productImageUrl($rel); @endphp
                        <a href="{{ route('website.product', $rel) }}" class="flex gap-3 group">
                            <img src="{{ $relImg }}" alt="" class="w-14 h-14 object-cover rounded-lg bg-slate-50 border border-slate-100">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-800 group-hover:text-blue-600 line-clamp-2">{{ $rel->name }}</p>
                                <p class="text-xs font-semibold text-blue-600 mt-0.5">{{ $ws->formatPrice($rel->currentPrice(), $settings) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Tabs: Description --}}
@if($product->short_description || $reviews->isNotEmpty())
<section class="mt-8 pt-6 border-t border-slate-100" x-data="{ tab: 'description' }">
    <div class="flex gap-6 border-b border-slate-100 mb-5">
        @if($product->short_description)
            <button type="button" @click="tab='description'"
                    :class="tab==='description' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                    class="pb-2 text-sm font-medium border-b-2 transition">Description</button>
        @endif
        @if($reviews->isNotEmpty())
            <button type="button" @click="tab='reviews'"
                    :class="tab==='reviews' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                    class="pb-2 text-sm font-medium border-b-2 transition">Reviews ({{ $reviews->count() }})</button>
        @endif
    </div>

    @if($product->short_description)
        <div x-show="tab==='description'" class="max-w-3xl">
            <h3 class="text-sm font-semibold text-slate-900 mb-2">About this item</h3>
            <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $product->short_description }}</div>
        </div>
    @endif

    @if($reviews->isNotEmpty())
        <div x-show="tab==='reviews'" x-cloak class="grid gap-3 md:grid-cols-2 max-w-4xl">
            @foreach($reviews as $review)
                <div class="rounded-xl border border-slate-100 bg-white p-4">
                    <div class="mb-1 text-amber-400 text-xs">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</div>
                    <p class="text-sm text-slate-600">“{{ $review->body }}”</p>
                    <p class="mt-2 text-xs font-medium text-slate-800">{{ $review->customer_name }}</p>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endif

@if($related->count())
<section class="mt-8 pt-6 border-t border-slate-100 lg:hidden">
    <h2 class="text-sm font-semibold text-slate-900 mb-3">Related products</h2>
    <div class="grid grid-cols-2 gap-3">
        @foreach($related as $rel)
            @include('website.partials.product-card', ['product' => $rel])
        @endforeach
    </div>
</section>
@endif
