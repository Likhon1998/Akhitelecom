{{-- Shared product form fields — used by create & edit --}}
@php
    $product = $product ?? null;
    $isEdit = $product !== null;
@endphp

<div class="space-y-5"
     x-data="{
        name: @js(old('name', $product?->name ?? '')),
        color: @js(old('color', $product?->color ?? '')),
        colorHex: @js(old('color_hex', $product?->color_hex ?: '#2563eb')),
        storage: @js(old('storage', $product?->storage ?? '')),
        ram: @js(old('ram', $product?->ram ?? '')),
        variantGroup: @js(old('variant_group', $product?->variant_group ?? '')),
        selling: @js(old('selling_price', $product?->selling_price ?? '')),
        autoGroup: true,
        categoryModal: false,
        brandModal: false,
        quickName: '',
        quickLoading: false,
        quickError: '',
        categoryUrl: @js(route('categories.store')),
        brandUrl: @js(route('brands.store')),
        csrf: @js(csrf_token()),
        slugify(s) {
            return String(s || '').toLowerCase().trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 80);
        },
        syncGroup() {
            if (this.autoGroup && !@js($isEdit && filled($product?->variant_group))) {
                this.variantGroup = this.slugify(this.name);
            }
        },
        pickSwatch(hex, label) {
            this.colorHex = hex;
            if (!this.color) this.color = label;
        },
        openCategoryModal() {
            this.quickName = '';
            this.quickError = '';
            this.categoryModal = true;
            this.$nextTick(() => this.$refs.quickCategoryInput?.focus());
        },
        openBrandModal() {
            this.quickName = '';
            this.quickError = '';
            this.brandModal = true;
            this.$nextTick(() => this.$refs.quickBrandInput?.focus());
        },
        async saveQuickCategory() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a category name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.categoryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create category.';
                    return;
                }
                const select = document.getElementById('category_id');
                const opt = document.createElement('option');
                opt.value = data.category.id;
                opt.textContent = data.category.name;
                opt.selected = true;
                select.appendChild(opt);
                this.categoryModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
        async saveQuickBrand() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a brand name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.brandUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name, is_active: true }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create brand.';
                    return;
                }
                const select = document.getElementById('brand_id');
                const opt = document.createElement('option');
                opt.value = data.brand.id;
                opt.textContent = data.brand.name;
                opt.selected = true;
                select.appendChild(opt);
                this.brandModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
     }"
     x-init="syncGroup()">

    {{-- 1. Gallery --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">1. Product gallery</h3>
            <p class="text-xs text-slate-500 mt-0.5">Add as many photos as you need — shown as a gallery on the store product page.</p>
        </div>
        <div class="p-4">
            @include('products.partials.image-uploads', ['product' => $product ?? null])
        </div>
    </section>

    {{-- 2. Basic info --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">2. Basic information</h3>
            <p class="text-xs text-slate-500 mt-0.5">Title, brand, and category customers see on the store.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product name <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="name" @input="syncGroup()" value="{{ old('name', $product?->name ?? '') }}" required autofocus
                       class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                       placeholder="e.g. iPhone 15 Pro Max">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="category_id" class="block text-xs font-semibold text-slate-600">Category</label>
                        <button type="button" @click="openCategoryModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add category">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="category_id" name="category_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product?->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="brand_id" class="block text-xs font-semibold text-slate-600">Brand</label>
                        <button type="button" @click="openBrandModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add brand">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="brand_id" name="brand_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">No brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product?->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Barcode <span class="text-red-500">*</span></label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode ?? '') }}" required
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="Scan or type…">
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SKU (optional)</label>
                    <input type="text" name="sku" value="{{ old('sku', $product?->sku ?? '') }}"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="e.g. IPH15-256-NAT">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Availability (shop filter)</label>
                    <select name="availability" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        @php $avail = old('availability', $product?->availability ?? 'in_stock'); @endphp
                        <option value="in_stock" @selected($avail === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected($avail === 'pre_order')>Pre Order</option>
                        <option value="up_coming" @selected($avail === 'up_coming')>Up Coming</option>
                        <option value="out_of_stock" @selected($avail === 'out_of_stock')>Out of Stock</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Used by category sidebar filters on the website.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Pricing --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">3. Pricing</h3>
            <p class="text-xs text-slate-500 mt-0.5">Selling price is the normal store price. Timed sales are set from the product list.</p>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cost price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product?->cost_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                @error('cost_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Selling price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="selling_price" x-model="selling" value="{{ old('selling_price', $product?->selling_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-medium text-blue-600">
                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- 4. Variants — matches storefront color/storage UI --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">4. Color, storage & RAM (store variants)</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Link same-model SKUs with one <strong>variant group</strong> key (example: <code class="text-[11px] bg-slate-100 px-1 rounded">samsung-s22</code>).
                Create a separate product for each color + RAM + storage combo. On the store page customers pick
                <strong>color</strong> first, then the available <strong>RAM / storage</strong> combinations.
            </p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <label class="text-xs font-semibold text-slate-600">Variant group key</label>
                    <label class="text-[11px] text-slate-500 inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="autoGroup" @change="syncGroup()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Auto from product name
                    </label>
                </div>
                <input type="text" name="variant_group" x-model="variantGroup" @input="autoGroup = false"
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                       placeholder="e.g. samsung-s22">
                <p class="text-[11px] text-slate-400 mt-1">
                    Example: 3 Green S22 rows (4GB/64GB, 8GB/128GB, 8GB/512GB) + Blue/Black rows — all use the same key.
                    Shop shows one card per group; the product page shows all colors and memory options.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Color name</label>
                    <input type="text" name="color" x-model="color"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                           placeholder="e.g. Natural Titanium, Red">
                    <div class="flex flex-wrap gap-2 mt-2.5">
                        @foreach([
                            ['#1e293b', 'Black'],
                            ['#f8fafc', 'White'],
                            ['#dc2626', 'Red'],
                            ['#2563eb', 'Blue'],
                            ['#d4cfc8', 'Natural Titanium'],
                            ['#3a3a3a', 'Black Titanium'],
                            ['#5b7a9d', 'Blue Titanium'],
                            ['#16a34a', 'Green'],
                            ['#ca8a04', 'Gold'],
                        ] as [$hex, $label])
                            <button type="button" @click="pickSwatch('{{ $hex }}', '{{ $label }}')"
                                    title="{{ $label }}"
                                    class="w-7 h-7 rounded-full border-2 border-white shadow ring-1 ring-slate-200 hover:ring-blue-400 transition"
                                    style="background: {{ $hex }}"></button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Swatch color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" x-model="colorHex"
                               class="h-10 w-14 rounded-lg border border-slate-200 cursor-pointer shrink-0">
                        <input type="text" name="color_hex" x-model="colorHex"
                               class="flex-1 rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                               placeholder="#2563eb">
                        <div class="w-10 h-10 rounded-full border-2 border-blue-600 ring-2 ring-blue-100 shrink-0"
                             :style="'background:' + colorHex" title="Preview"></div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">This circle appears on the product page.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Storage / size</label>
                    <input type="text" name="storage" x-model="storage" list="storage-presets"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                           placeholder="e.g. 256GB">
                    <datalist id="storage-presets">
                        <option value="64GB"><option value="128GB"><option value="256GB"><option value="512GB"><option value="1TB">
                    </datalist>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach(['128GB', '256GB', '512GB', '1TB'] as $s)
                            <button type="button" @click="storage = '{{ $s }}'"
                                    class="px-2.5 py-1 rounded-md border text-xs font-medium transition"
                                    :class="storage === '{{ $s }}' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                {{ $s }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">RAM</label>
                    <input type="text" name="ram" x-model="ram" list="ram-presets"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                           placeholder="e.g. 8GB">
                    <datalist id="ram-presets">
                        <option value="4GB"><option value="6GB"><option value="8GB"><option value="12GB"><option value="16GB"><option value="32GB"><option value="64GB">
                    </datalist>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach(['4GB', '8GB', '12GB', '16GB', '32GB'] as $r)
                            <button type="button" @click="ram = '{{ $r }}'"
                                    class="px-2.5 py-1 rounded-md border text-xs font-medium transition"
                                    :class="ram === '{{ $r }}' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                {{ $r }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Live storefront-style preview --}}
            <div class="rounded-lg border border-dashed border-blue-200 bg-blue-50/40 p-3">
                <p class="text-[11px] font-semibold text-blue-700 uppercase tracking-wide mb-2">Store preview</p>
                <p class="text-sm font-semibold text-slate-900" x-text="name || 'Product name'"></p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-sm font-semibold text-blue-600" x-text="selling ? ('Tk ' + Number(selling).toLocaleString()) : 'Tk —'"></span>
                </div>
                <p class="text-xs text-slate-600 mt-2" x-show="storage">
                    Storage: <span class="font-medium" x-text="storage"></span>
                </p>
                <p class="text-xs text-slate-600 mt-1" x-show="ram">
                    RAM: <span class="font-medium" x-text="ram"></span>
                </p>
                <div class="flex items-center gap-2 mt-2" x-show="color || colorHex">
                    <span class="text-xs text-slate-600">Color: <span class="font-medium" x-text="color || '—'"></span></span>
                    <span class="w-5 h-5 rounded-full border-2 border-blue-600 ring-1 ring-blue-100 inline-block" :style="'background:' + colorHex"></span>
                </div>
                <p class="text-[11px] text-slate-400 mt-2" x-show="variantGroup">
                    Group: <code class="bg-white px-1 rounded border border-slate-100" x-text="variantGroup"></code>
                </p>
            </div>
        </div>
    </section>

    {{-- 5. Store description --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">5. Store description & visibility</h3>
            <p class="text-xs text-slate-500 mt-0.5">Shown under Description on the product page.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Short description</label>
                <textarea name="short_description" rows="4"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="About this item — features, condition, what’s included…">{{ old('short_description', $product?->short_description ?? '') }}</textarea>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_published', $isEdit ? ($product?->is_published ?? true) : true) ? 'checked' : '' }}>
                    Publish on website
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_new_arrival" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_new_arrival', $product?->is_new_arrival ?? false) ? 'checked' : '' }}>
                    New Arrival
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_best_seller" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_best_seller', $product?->is_best_seller ?? false) ? 'checked' : '' }}>
                    Trending
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_featured', $product?->is_featured ?? false) ? 'checked' : '' }}>
                    Featured
                </label>
            </div>
            <p class="text-[11px] text-slate-400">New Arrival and Trending control which products appear in those homepage sections.</p>

            @if(!$isEdit)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Opening quantity</label>
                        <input type="number" name="stock_quantity" min="0" step="1"
                               value="{{ old('stock_quantity', 0) }}"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 10">
                        <p class="text-[11px] text-slate-400 mt-1">How many units you have now. Enter 0 if you’ll stock later.</p>
                        @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" required min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
                <div class="rounded-lg border border-blue-100 bg-blue-50/50 px-3 py-2.5 text-xs text-slate-600">
                    Quantity entered here becomes <strong>opening stock</strong> (same ledger entry as Opening Inventory).
                    Leave <strong>0</strong> if the product isn’t in hand yet — you can still set it later in Opening Inventory.
                    For already-stocked products, use <a href="{{ route('supply.adjustments.index') }}" class="text-blue-600 font-medium underline">Stock Adjustment</a> to change qty.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Current stock</label>
                        <div class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm py-2.5 px-3 font-medium text-slate-900">
                            {{ $product?->stock_quantity ?? 0 }} units
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Change stock via
                            @if(($product?->stock_quantity ?? 0) === 0)
                                <a href="{{ route('supply.opening-inventory.index') }}" class="text-blue-600 underline">Opening Inventory</a>
                                or
                            @endif
                            <a href="{{ route('supply.adjustments.index') }}" class="text-blue-600 underline">Stock Adjustment</a>.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', $product?->alert_quantity ?? 5) }}" required min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Quick add category modal --}}
    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="categoryModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="categoryModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add category</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a category without leaving this page.</p>
                </div>
                <button type="button" @click="categoryModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Category name</label>
            <input type="text" x-ref="quickCategoryInput" x-model="quickName" @keydown.enter.prevent="saveQuickCategory()"
                   placeholder="e.g. Phones"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="categoryModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickCategory()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add category'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Quick add brand modal --}}
    <div x-show="brandModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="brandModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="brandModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add brand</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a brand without leaving this page.</p>
                </div>
                <button type="button" @click="brandModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Brand name</label>
            <input type="text" x-ref="quickBrandInput" x-model="quickName" @keydown.enter.prevent="saveQuickBrand()"
                   placeholder="e.g. Apple"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="brandModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickBrand()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add brand'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
