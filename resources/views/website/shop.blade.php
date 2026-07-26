@extends('website.layout')

@section('content')
@php
    $from = $products->firstItem() ?? 0;
    $to = $products->lastItem() ?? 0;
    $total = $products->total();
    $viewMode = request('view', 'grid');
@endphp

<div class="gs-shop" x-data="{ view: @js($viewMode) }">
    <div class="gs-shop-inner">
        @if(!empty($showSidebar) && !empty($sidebarFacets))
            @include('website.partials.category-filters')
        @else
            @include('website.partials.shop-sidebar')
        @endif

        <div class="gs-main">
            <nav class="gs-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>›</span>
                <span class="gs-breadcrumb-current">Shop</span>
            </nav>

            <div class="gs-toolbar">
                <div class="gs-toolbar-left">
                    <h1 class="gs-title">{{ $pageTitle ?? 'Shop' }}</h1>
                    <p class="gs-count">
                        Showing {{ $from }}–{{ $to }} of {{ number_format($total) }} products
                    </p>
                </div>

                <div class="gs-toolbar-right">
                    <div class="gs-view-toggle" role="group" aria-label="View mode">
                        <button type="button" class="gs-view-btn" :class="view === 'grid' && 'is-active'" @click="view = 'grid'" title="Grid view">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                        </button>
                        <button type="button" class="gs-view-btn" :class="view === 'list' && 'is-active'" @click="view = 'list'" title="List view">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('website.shop') }}" class="gs-sort">
                        @foreach(request()->except('sort', 'page') as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="gs-sort">Sort by:</label>
                        <select id="gs-sort" name="sort" onchange="this.form.submit()">
                            <option value="featured" @selected(($sort ?? request('sort', 'featured')) === 'featured')>Featured</option>
                            <option value="latest" @selected(($sort ?? '') === 'latest')>Latest</option>
                            <option value="price_asc" @selected(($sort ?? '') === 'price_asc')>Price: Low to High</option>
                            <option value="price_desc" @selected(($sort ?? '') === 'price_desc')>Price: High to Low</option>
                            <option value="name" @selected(($sort ?? '') === 'name')>Name</option>
                            <option value="bestsellers" @selected(($sort ?? '') === 'bestsellers')>Best Sellers</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="gs-grid" :class="view === 'list' && 'gs-grid--list'">
                @forelse($products as $product)
                    @include('website.partials.product-card', ['product' => $product, 'listMode' => true])
                @empty
                    <div class="gs-empty">
                        <p>No products found.</p>
                        <a href="{{ route('website.shop') }}">Clear filters</a>
                    </div>
                @endforelse
            </div>

            @if($products->hasPages())
                <div class="gs-pagination">
                    {{ $products->onEachSide(2)->links('website.partials.shop-pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
