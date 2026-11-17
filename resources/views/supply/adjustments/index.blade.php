<x-supply-layout
    title="Stock Adjustment"
    subtitle="Manual stock in/out plus full movement history. Sales and transfers change stock automatically — shown below with clear + / − signs."
>
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'sku' => $p->barcode ?: ($p->sku ?: ''),
            'stock' => (int) $p->stock_quantity,
            'phone' => (bool) $p->requires_imei,
        ])->values();
        $oldProductId = old('product_id');
    @endphp

    {{-- Manual adjustment / restock form with product search --}}
    <form method="POST" action="{{ route('supply.adjustments.store') }}"
          class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 mb-6 grid md:grid-cols-4 gap-4 max-w-5xl shadow-sm"
          x-data="stockAdjustmentForm(@js($productOptions), @js((object) $imeiStock), @js($oldProductId ? (string) $oldProductId : null))"
          @submit="if (!productId) { $event.preventDefault(); open = true; }">
        @csrf
        <div class="md:col-span-2 relative">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Product</label>
            <div class="relative mt-1">
                <input type="search"
                       x-model="query"
                       @focus="open = true"
                       @input="open = true"
                       @keydown.escape.window="open = false"
                       @click.outside="open = false"
                       placeholder="Search by name, barcode or SKU…"
                       class="w-full rounded-xl border-gray-200 py-2.5 pl-10 pr-3 text-sm"
                       autocomplete="off">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="hidden" name="product_id" :value="productId" required>
            <div x-show="open" x-cloak
                 class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                <template x-for="p in filtered()" :key="p.id">
                    <button type="button"
                            @click="selectProduct(p)"
                            class="flex w-full items-center justify-between gap-3 border-b border-gray-50 px-3.5 py-2.5 text-left last:border-0 hover:bg-indigo-50"
                            :class="String(productId) === String(p.id) && 'bg-indigo-50'">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-gray-900" x-text="p.name"></div>
                            <div class="truncate text-xs text-gray-400">
                                <span x-text="p.sku || 'No barcode'"></span>
                                <span x-show="p.phone" class="text-orange-600 font-semibold"> · IMEI</span>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600"
                              x-text="p.stock + ' on hand'"></span>
                    </button>
                </template>
                <div x-show="filtered().length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                    No products match that search.
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500" x-show="selected" x-cloak>
                Selected:
                <span class="font-semibold text-gray-800" x-text="selected.name"></span>
                <span class="text-gray-400">·</span>
                <span x-text="selected.stock + ' on hand'"></span>
            </p>
            @error('product_id')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Direction</label>
            <select name="type" x-model="type" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required>
                <option value="in">Stock In (+)</option>
                <option value="out">Stock Out (−)</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Quantity</label>
            <input type="number" name="quantity" x-model="qty" min="1" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required
                   :readonly="imeiMode() === 'pick'" :class="imeiMode() === 'pick' ? 'bg-slate-50' : ''">
        </div>
        <div class="md:col-span-4" x-show="imeiMode()" x-cloak>
            @include('supply.partials.imei-fields', [
                'mode' => 'imeiMode()',
                'options' => '(stock[productId] || [])',
                'picked' => 'picked',
                'phones' => 'phones',
                'qty' => 'qty',
                'pickName' => "'imeis[]'",
                'enterName' => "'phones'",
            ])
        </div>
        <div class="md:col-span-4">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Reason / Reference</label>
            <input name="reference" value="{{ old('reference') }}" class="w-full rounded-xl border-gray-200 mt-1 text-sm" placeholder="e.g. Restock from supplier / Physical count" required>
        </div>
        <div class="md:col-span-4 flex flex-wrap items-center gap-3">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold">Save Adjustment</button>
            <p class="text-[11px] text-gray-500">
                Search a product above, then use <span class="font-bold text-emerald-600">Stock In</span> to restock.
                <span class="font-bold text-rose-600">− red</span> = stock out / sale / damage.
            </p>
        </div>
    </form>

    {{-- Live filters (no full page reload) --}}
    <div class="mb-4 space-y-3"
         x-data="{
            search: @js(request('search', '')),
            direction: @js(request('direction', '')),
            source: @js(request('source', '')),
            startDate: @js(request('start_date', '')),
            endDate: @js(request('end_date', '')),
            baseUrl: @js(route('supply.adjustments.index')),
            busy: false,
            timer: null,
            hasFilters() {
                return !!(this.search || this.direction || this.source || this.startDate || this.endDate);
            },
            buildUrl(page) {
                const url = new URL(this.baseUrl, window.location.origin);
                if (this.search) url.searchParams.set('search', this.search);
                if (this.direction) url.searchParams.set('direction', this.direction);
                if (this.source) url.searchParams.set('source', this.source);
                if (this.startDate) url.searchParams.set('start_date', this.startDate);
                if (this.endDate) url.searchParams.set('end_date', this.endDate);
                if (page) url.searchParams.set('page', page);
                return url;
            },
            async refresh(page) {
                if (this.busy) return;
                this.busy = true;
                const url = this.buildUrl(page);
                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const next = doc.getElementById('sa-results');
                    const cur = document.getElementById('sa-results');
                    if (next && cur) cur.outerHTML = next.outerHTML;
                    history.replaceState({}, '', url);
                } catch (e) {
                    window.location = url.toString();
                } finally {
                    this.busy = false;
                }
            },
            onSearch() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.refresh(), 350);
            },
            clearAll() {
                this.search = '';
                this.direction = '';
                this.source = '';
                this.startDate = '';
                this.endDate = '';
                this.refresh();
            },
            onPagerClick(e) {
                const a = e.target.closest('a');
                if (!a || !a.closest('[data-sa-pagination]')) return;
                e.preventDefault();
                try {
                    const page = new URL(a.href).searchParams.get('page');
                    this.refresh(page);
                } catch (err) {}
            },
         }"
         @click="onPagerClick($event)">
        <div class="flex flex-wrap gap-2 items-center">
            <input type="search"
                   x-model="search"
                   @input="onSearch()"
                   placeholder="Search product, reason, reference…"
                   class="rounded-xl border-gray-200 text-sm min-w-[200px] flex-1">
            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
                From
                <input type="date"
                       x-model="startDate"
                       @change="refresh()"
                       class="rounded-xl border-gray-200 text-sm font-semibold text-slate-700">
            </label>
            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
                To
                <input type="date"
                       x-model="endDate"
                       @change="refresh()"
                       class="rounded-xl border-gray-200 text-sm font-semibold text-slate-700">
            </label>
            <select x-model="direction"
                    @change="refresh()"
                    class="rounded-xl border-gray-200 text-sm">
                <option value="">All directions</option>
                <option value="in">In only (+)</option>
                <option value="out">Out only (−)</option>
            </select>
            <select x-model="source"
                    @change="refresh()"
                    class="rounded-xl border-gray-200 text-sm">
                <option value="">All sources</option>
                <option value="sale">Sales</option>
                <option value="transfer">Transfers</option>
                <option value="adjustment">Manual adjustments</option>
                <option value="purchase">Purchase receive / return</option>
                <option value="damage">Damage</option>
                <option value="opening">Opening stock</option>
            </select>
            <button type="button"
                    x-show="hasFilters()"
                    x-cloak
                    @click="clearAll()"
                    class="text-sm font-semibold text-gray-500 px-3 py-2 hover:text-slate-800">
                Clear
            </button>
            <span x-show="busy" x-cloak class="text-[11px] font-bold text-slate-400">Updating…</span>
        </div>

        <div :class="busy ? 'opacity-50 pointer-events-none' : ''">
            @include('supply.adjustments.partials.results')
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
    <script>
        function stockAdjustmentForm(products, imeiStock, initialId) {
            return {
                products: products || [],
                stock: imeiStock || {},
                query: '',
                open: false,
                productId: initialId ? String(initialId) : '',
                type: @js(old('type', 'in')),
                qty: @js(old('quantity', '')),
                picked: @js(array_values(old('imeis', []))),
                phones: @js(array_values(old('phones', []))),
                get selected() {
                    return this.products.find(p => String(p.id) === String(this.productId)) || null;
                },
                isPhone() {
                    return !!(this.selected && this.selected.phone);
                },
                imeiMode() {
                    return this.isPhone() ? (this.type === 'out' ? 'pick' : 'enter') : '';
                },
                filtered() {
                    const q = String(this.query || '').trim().toLowerCase();
                    const list = !q
                        ? this.products
                        : this.products.filter(p =>
                            String(p.name || '').toLowerCase().includes(q) ||
                            String(p.sku || '').toLowerCase().includes(q)
                        );
                    return list.slice(0, 50);
                },
                selectProduct(p) {
                    this.productId = String(p.id);
                    this.query = p.name;
                    this.open = false;
                    this.picked = [];
                    this.phones = [];
                },
                init() {
                    if (this.selected) {
                        this.query = this.selected.name;
                    }
                },
            };
        }
    </script>
</x-supply-layout>
