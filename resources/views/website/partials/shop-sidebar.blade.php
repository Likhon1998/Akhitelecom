@php
    $boundMin = (float) ($priceBounds['min'] ?? 0);
    $boundMax = max($boundMin + 1, (float) ($priceBounds['max'] ?? 1000));
    $minPrice = request()->filled('min_price') ? (float) request('min_price') : $boundMin;
    $maxPrice = request()->filled('max_price') ? (float) request('max_price') : $boundMax;
    $selectedBrands = array_map('intval', (array) request('brands', []));
    $selectedStorages = array_map('strval', (array) request('storage', []));
    $selectedRams = array_map('strval', (array) request('ram', []));
    $categories = $categories ?? collect();
    $brands = $brands ?? collect();
    $storageOptions = $storageOptions ?? collect();
    $ramOptions = $ramOptions ?? collect();
    $categoryTotal = (int) ($categoryTotal ?? $categories->sum('published_count'));
    $activeCat = request('category')
        ?: (isset($activeCategory) ? ($activeCategory->slug ?: $activeCategory->id) : null);
    $symbol = $settings->currency_symbol ?? '৳';
    $visibleBrands = 5;
@endphp

<aside class="gs-sidebar">
    <form method="GET"
          action="{{ route('website.shop') }}"
          class="gs-sidebar-card"
          data-shop-filters
          data-no-loader
          @submit.prevent="$dispatch('shop-refresh')"
          x-data="{
              min: {{ (int) round($minPrice) }},
              max: {{ (int) round($maxPrice) }},
              boundMin: {{ (int) round($boundMin) }},
              boundMax: {{ (int) round($boundMax) }},
              brandsOpen: false,
              syncMin() { if (this.min > this.max) this.min = this.max; },
              syncMax() { if (this.max < this.min) this.max = this.min; },
              get pctMin() { return ((this.min - this.boundMin) / (this.boundMax - this.boundMin || 1)) * 100; },
              get pctMax() { return ((this.max - this.boundMin) / (this.boundMax - this.boundMin || 1)) * 100; },
          }">
        @if(request('filter'))
            <input type="hidden" name="filter" value="{{ request('filter') }}">
        @endif
        @if(request('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif
        @if($activeCat)
            <input type="hidden" name="category" value="{{ $activeCat }}">
        @endif

        {{-- Categories --}}
        <div class="gs-filter-block">
            <h3 class="gs-filter-title">Categories</h3>
            <ul class="gs-cat-list">
                <li>
                    <a href="{{ route('website.shop', request()->except('category', 'page')) }}"
                       class="gs-cat-link {{ ! $activeCat ? 'is-active' : '' }}"
                       data-category=""
                       data-no-loader>
                        <span>All Categories</span>
                        <span class="gs-cat-count">({{ number_format($categoryTotal) }})</span>
                    </a>
                </li>
                @foreach($categories as $cat)
                    @php $catKey = $cat->slug ?: $cat->id; @endphp
                    <li>
                        <a href="{{ route('website.shop', array_merge(request()->except('page'), ['category' => $catKey])) }}"
                           class="gs-cat-link {{ (string) $activeCat === (string) $catKey || (string) $activeCat === (string) $cat->id ? 'is-active' : '' }}"
                           data-category="{{ $catKey }}"
                           data-no-loader>
                            <span>{{ $cat->name }}</span>
                            <span class="gs-cat-count">({{ number_format((int) ($cat->published_count ?? 0)) }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- RAM --}}
        @if($ramOptions->isNotEmpty())
            <div class="gs-filter-block">
                <h3 class="gs-filter-title">RAM</h3>
                <ul class="gs-brand-list">
                    @foreach($ramOptions as $ram)
                        <li>
                            <label class="gs-check">
                                <input type="checkbox" name="ram[]" value="{{ $ram }}"
                                       @checked(in_array((string) $ram, $selectedRams, true))
                                       @change="$dispatch('shop-refresh')">
                                <span class="gs-check-label">{{ $ram }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ROM / Storage --}}
        @if($storageOptions->isNotEmpty())
            <div class="gs-filter-block">
                <h3 class="gs-filter-title">ROM / Storage</h3>
                <ul class="gs-brand-list">
                    @foreach($storageOptions as $storage)
                        <li>
                            <label class="gs-check">
                                <input type="checkbox" name="storage[]" value="{{ $storage }}"
                                       @checked(in_array((string) $storage, $selectedStorages, true))
                                       @change="$dispatch('shop-refresh')">
                                <span class="gs-check-label">{{ $storage }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Price --}}
        <div class="gs-filter-block">
            <h3 class="gs-filter-title">Filter By Price</h3>
            <div class="gs-price-slider">
                <div class="gs-price-track">
                    <div class="gs-price-range" :style="'left:' + pctMin + '%; right:' + (100 - pctMax) + '%'"></div>
                    <input type="range" :min="boundMin" :max="boundMax" step="1" x-model.number="min" @input="syncMin()" class="gs-range gs-range-min">
                    <input type="range" :min="boundMin" :max="boundMax" step="1" x-model.number="max" @input="syncMax()" class="gs-range gs-range-max">
                </div>
                <input type="hidden" name="min_price" :value="min">
                <input type="hidden" name="max_price" :value="max">
                <div class="gs-price-meta">
                    <span>{{ $symbol }}<span x-text="min"></span> to {{ $symbol }}<span x-text="max"></span>+</span>
                    <button type="submit" class="gs-price-btn">Filter</button>
                </div>
            </div>
        </div>

        {{-- Brands --}}
        @if($brands->isNotEmpty())
            <div class="gs-filter-block">
                <h3 class="gs-filter-title">Brands</h3>
                <ul class="gs-brand-list">
                    @foreach($brands as $i => $brand)
                        <li @if($i >= $visibleBrands) x-show="brandsOpen" x-cloak @endif>
                            <label class="gs-check">
                                <input type="checkbox" name="brands[]" value="{{ $brand->id }}"
                                       @checked(in_array((int) $brand->id, $selectedBrands, true))
                                       @change="$dispatch('shop-refresh')">
                                <span class="gs-check-label">{{ $brand->name }}</span>
                                <span class="gs-cat-count">({{ number_format((int) ($brand->published_count ?? 0)) }})</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                @if($brands->count() > $visibleBrands)
                    <button type="button" class="gs-view-more" @click="brandsOpen = !brandsOpen"
                            x-text="brandsOpen ? '− Show Less' : '+ View More'"></button>
                @endif
            </div>
        @endif
    </form>
</aside>
