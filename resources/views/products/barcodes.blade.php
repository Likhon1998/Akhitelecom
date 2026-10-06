<x-app-layout>
    <div class="max-w-7xl mx-auto pt-0 pb-10 px-4 sm:px-6 lg:px-8"
         x-data="barcodePrinter(@js($products->getCollection()->map(fn ($p) => [
             'id' => $p->id,
             'name' => $p->name,
             'barcode' => $p->barcode,
             'price' => (float) $p->selling_price,
             'image' => $p->image ? public_storage_url($p->image) : null,
             'category' => $p->category?->name,
         ])->values()), @js(route('products.barcodes.print')))">

        <div class="mb-5 mt-3 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Print Barcodes</h2>
                <p class="mt-0.5 text-sm text-slate-500">Select products and print barcode labels individually or in bulk.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Back to products
                </a>
                <button type="button" @click="printSelected()" :disabled="selected.length === 0"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print selected (<span x-text="selected.length"></span>)
                </button>
            </div>
        </div>

        <div class="mb-4 grid gap-3 lg:grid-cols-[1fr_auto_auto] items-end">
            <form method="GET" action="{{ route('products.barcodes') }}" class="flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, barcode, SKU…"
                       class="min-w-[220px] flex-1 rounded-xl border-slate-200 bg-white px-3.5 py-2 text-sm focus:border-blue-400 focus:ring-blue-100">
                <select name="category_id" class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:ring-blue-100">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Search</button>
                @if(request()->filled('q') || request()->filled('category_id'))
                    <a href="{{ route('products.barcodes') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Clear</a>
                @endif
            </form>

            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <span class="font-medium">Copies each</span>
                <input type="number" x-model.number="copies" min="1" max="20"
                       class="w-20 rounded-xl border-slate-200 px-2.5 py-2 text-sm focus:border-blue-400 focus:ring-blue-100">
            </label>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                       :checked="allSelected" @change="toggleAll($event.target.checked)">
                Select all on page
            </label>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="w-12 px-4 py-3"></th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Barcode</th>
                            <th class="px-4 py-3">Preview</th>
                            <th class="px-4 py-3 text-right">Price</th>
                            <th class="px-4 py-3 text-right">Print</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80" :class="isSelected({{ $product->id }}) ? 'bg-blue-50/40' : ''">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                           :checked="isSelected({{ $product->id }})"
                                           @change="toggle({{ $product->id }})">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-100 bg-slate-50">
                                            @if($product->image)
                                                <img src="{{ public_storage_url($product->image) }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorized' }}@if($product->brand?->name || $product->brand_name) · {{ $product->brand?->name ?? $product->brand_name }}@endif</p>
                                            @if($product->requires_imei)
                                                <p class="mt-1 text-[11px] font-semibold {{ $product->phones_in_stock ? 'text-orange-700' : 'text-rose-600' }}">
                                                    @if($product->phones_in_stock)
                                                        {{ $product->phones_in_stock }} phone(s) in stock — prints one IMEI label per phone
                                                    @else
                                                        No phones with IMEI in stock
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[12px] text-slate-700">{{ $product->barcode }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="barcode-preview inline-flex rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                                        <svg class="js-barcode" data-value="{{ $product->barcode }}"></svg>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">
                                    Tk {{ number_format($product->selling_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="printOne({{ $product->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Print
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-16 text-center text-sm text-slate-400">
                                    No products found. Add products first, then print barcodes here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>
            @endif
        </div>

        <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-[12px] text-blue-800">
            Tip: tick products and use <strong>Print selected</strong>, or hit <strong>Print</strong> on one row for a single label. Set copies if you need multiple stickers of the same barcode.
            Phones print one label per phone with its own IMEI barcode — scan that label at POS to sell exactly that phone. You can also search by IMEI here.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        function barcodePrinter(pageProducts, printUrl) {
            return {
                pageProducts,
                printUrl,
                selected: [],
                copies: 1,
                get allSelected() {
                    return this.pageProducts.length > 0 && this.pageProducts.every((p) => this.selected.includes(p.id));
                },
                isSelected(id) {
                    return this.selected.includes(id);
                },
                toggle(id) {
                    if (this.isSelected(id)) {
                        this.selected = this.selected.filter((x) => x !== id);
                    } else {
                        this.selected.push(id);
                    }
                },
                toggleAll(checked) {
                    const ids = this.pageProducts.map((p) => p.id);
                    if (checked) {
                        this.selected = Array.from(new Set([...this.selected, ...ids]));
                    } else {
                        this.selected = this.selected.filter((id) => !ids.includes(id));
                    }
                },
                openPrint(ids) {
                    if (!ids.length) return;
                    const params = new URLSearchParams({
                        product_ids: ids.join(','),
                        copies: String(Math.max(1, Math.min(20, this.copies || 1))),
                    });
                    window.location.href = `${this.printUrl}?${params.toString()}`;
                },
                printOne(id) {
                    this.openPrint([id]);
                },
                printSelected() {
                    this.openPrint(this.selected);
                },
                init() {
                    this.$nextTick(() => this.renderBarcodes());
                },
                renderBarcodes() {
                    document.querySelectorAll('svg.js-barcode').forEach((el) => {
                        const value = el.getAttribute('data-value');
                        if (!value) return;
                        try {
                            JsBarcode(el, value, {
                                format: 'CODE128',
                                width: 1.4,
                                height: 36,
                                displayValue: true,
                                fontSize: 11,
                                margin: 4,
                                background: '#ffffff',
                            });
                        } catch (e) {}
                    });
                },
            };
        }
    </script>
</x-app-layout>
