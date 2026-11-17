@php
    $isWarehouse = str_contains(request()->route()->getName(), 'warehouses');
    $label = $isWarehouse ? 'Warehouse' : 'Store';
    $canAdd = $locations->isEmpty();
@endphp
<x-supply-layout
    :title="$label . ' Settings'"
    :subtitle="$isWarehouse ? 'Back-room storage — receive here, then transfer to store to sell.' : 'Your retail store — sellable stock for POS & web.'"
    :action-url="$canAdd ? route($isWarehouse ? 'supply.warehouses.create' : 'supply.stores.create') : null"
    :action-label="$canAdd ? '+ Add ' . $label : null">
    @if (! $canAdd)
        <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm text-indigo-900">
            One {{ strtolower($label) }} per business. Edit the details below — you cannot add another {{ strtolower($label) }}.
        </div>
    @endif
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm mb-6">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                <tr><th class="p-4">Name</th><th class="p-4">Address</th><th class="p-4">Default</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($locations as $location)
                    <tr>
                        <td class="p-4 font-semibold">{{ $location->name }}</td>
                        <td class="p-4 text-gray-500">{{ $location->address ?? '—' }}</td>
                        <td class="p-4"><span class="text-emerald-600 font-bold">{{ $location->is_default ? 'Yes' : '—' }}</span></td>
                        <td class="p-4">{{ $location->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="p-4 text-right">
                            <a href="{{ route($isWarehouse ? 'supply.warehouses.edit' : 'supply.stores.edit', $location) }}" class="text-indigo-600 font-bold">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-gray-400">No {{ strtolower($label) }} configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($isWarehouse)
        @php
            $stockRows = $locations->flatMap(fn ($loc) => $loc->warehouseStocks->map(fn ($ws) => [
                'location' => $loc->name,
                'product' => $ws->product?->name ?? '—',
                'qty' => (int) $ws->quantity,
            ]))->filter(fn ($r) => $r['qty'] > 0)->values();
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm"
             x-data="{
                rows: @js($stockRows),
                page: 1,
                perPage: 25,
                get filteredCount() { return this.rows.length; },
                get totalPages() { return Math.max(1, Math.ceil(this.filteredCount / this.perPage)); },
                get paginated() {
                    const start = (this.page - 1) * this.perPage;
                    return this.rows.slice(start, start + this.perPage);
                },
                get rangeStart() { return this.filteredCount ? (this.page - 1) * this.perPage + 1 : 0; },
                get rangeEnd() { return Math.min(this.page * this.perPage, this.filteredCount); },
                goToPage(p) { this.page = Math.min(Math.max(1, p), this.totalPages); },
             }">
            <div class="px-5 py-4 border-b bg-slate-50 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-900">Warehouse stock on hand</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Not sellable until transferred to the store.</p>
                </div>
                <a href="{{ route('supply.stock-transfers.create') }}" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg">Transfer to store</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-4 text-left">Product</th>
                        <th class="p-4 text-left">Warehouse</th>
                        <th class="p-4 text-right">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(row, idx) in paginated" :key="'ws-' + idx + '-' + row.product">
                        <tr>
                            <td class="p-4 font-semibold" x-text="row.product"></td>
                            <td class="p-4 text-gray-500" x-text="row.location"></td>
                            <td class="p-4 text-right font-bold" x-text="row.qty"></td>
                        </tr>
                    </template>
                    <tr x-show="rows.length === 0">
                        <td colspan="3" class="p-10 text-center text-gray-400">No warehouse stock yet. Receive a PO into the warehouse first.</td>
                    </tr>
                </tbody>
            </table>
            <div x-show="rows.length > 0">
                <x-alpine-pager />
            </div>
        </div>
    @endif
</x-supply-layout>
