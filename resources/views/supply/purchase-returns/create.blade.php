<x-supply-layout title="New Purchase Return" subtitle="Return goods from store or warehouse. Stock ↓ · Accounts Payable ↓.">
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'cost' => (float) $p->cost_price,
            'stock' => (int) $p->stock_quantity,
            'phone' => (bool) $p->requires_imei,
        ])->values();

        $locationOptions = $locations->map(function ($l) use ($warehouseQty) {
            $qtyMap = [];
            if ($l->type === 'warehouse') {
                $qtyMap = ($warehouseQty[$l->id] ?? collect())->toArray();
            }
            return [
                'id' => $l->id,
                'type' => $l->type,
                'label' => ucfirst($l->type) . ': ' . $l->name,
                'warehouse_qty' => $qtyMap,
            ];
        })->values();

        $poOptions = $purchaseOrders->map(fn ($po) => [
            'id' => $po->id,
            'label' => $po->po_number . ' — ' . ($po->supplier->name ?? ''),
            'supplier_id' => (string) $po->supplier_id,
        ])->values();
    @endphp

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">
        <p class="font-semibold text-gray-800 mb-1">How it works</p>
        <ol class="list-decimal list-inside space-y-1 text-xs sm:text-sm">
            <li>Pick supplier (linking a PO auto-fills the supplier)</li>
            <li>Choose return location — store uses sellable stock; warehouse uses warehouse qty</li>
            <li>Stock decreases · Inventory asset ↓ · Accounts Payable ↓</li>
        </ol>
    </div>

    @if($suppliers->isEmpty())
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Add a <a href="{{ route('supply.suppliers.create') }}" class="font-bold underline">supplier</a> first.
        </div>
    @endif

    <form method="POST"
          action="{{ route('supply.purchase-returns.store') }}"
          class="space-y-5"
          x-data="{
              products: @js($productOptions),
              locations: @js($locationOptions),
              pos: @js($poOptions),
              supplierId: '{{ old('supplier_id', '') }}',
              poId: '{{ old('purchase_order_id', '') }}',
              locationId: '{{ old('return_location_id', $defaultLocationId) }}',
              imeiStock: @js($imeiStock),
              rows: [{ key: Date.now(), product_id: '', quantity: 1, unit_cost: 0, picked: [], phones: [] }],
              get location() { return this.locations.find(l => String(l.id) === String(this.locationId)); },
              onPoChange() {
                  const po = this.pos.find(p => String(p.id) === String(this.poId));
                  if (po) this.supplierId = String(po.supplier_id);
              },
              addRow() { this.rows.push({ key: Date.now()+Math.random(), product_id: '', quantity: 1, unit_cost: 0, picked: [], phones: [] }); },
              removeRow(i) { this.rows.length > 1 ? this.rows.splice(i,1) : this.rows=[{ key: Date.now(), product_id: '', quantity: 1, unit_cost: 0, picked: [], phones: [] }]; },
              isPhone(row) { return !!(this.products.find(x => String(x.id) === String(row.product_id)) || {}).phone; },
              phonesHere(row) {
                  const loc = this.location;
                  if (!loc) return [];
                  const pool = loc.type === 'warehouse' ? ((this.imeiStock.warehouse || {})[loc.id] || {}) : (this.imeiStock.store || {});
                  return pool[row.product_id] || [];
              },
              onProduct(row) {
                  const p = this.products.find(x => String(x.id) === String(row.product_id));
                  if (p) row.unit_cost = Number(p.cost) || 0;
              },
              available(row) {
                  const p = this.products.find(x => String(x.id) === String(row.product_id));
                  if (!p) return '—';
                  const loc = this.location;
                  if (!loc) return p.stock;
                  if (loc.type === 'warehouse') {
                      return Number(loc.warehouse_qty[p.id] || 0);
                  }
                  return p.stock;
              },
              line(row) { return (Number(row.quantity)||0) * (Number(row.unit_cost)||0); },
              total() { return this.rows.reduce((s,r)=>s+this.line(r),0); },
              money(n) { return '৳'+Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
          }"
          @if($suppliers->isEmpty() || $products->isEmpty()) onsubmit="return false" @endif>
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Linked PO (optional)</label>
                <select name="purchase_order_id" x-model="poId" @change="onPoChange()" class="w-full text-sm rounded-lg border-gray-200 py-1.5">
                    <option value="">—</option>
                    <template x-for="po in pos" :key="po.id">
                        <option :value="po.id" x-text="po.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Supplier</label>
                <select name="supplier_id" x-model="supplierId" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                    <option value="">Select…</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Return from</label>
                <select name="return_location_id" x-model="locationId" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional" class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-400">
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
                <h3 class="text-sm font-bold">Return lines</h3>
                <button type="button" @click="addRow()" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1.5 rounded-lg">+ Add line</button>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] uppercase tracking-wider text-gray-400 border-b">
                        <th class="text-left font-semibold px-4 py-2">Product</th>
                        <th class="font-semibold px-2 py-2 w-24">Available</th>
                        <th class="font-semibold px-2 py-2 w-24">Qty</th>
                        <th class="font-semibold px-2 py-2 w-28">Unit cost</th>
                        <th class="text-right font-semibold px-2 py-2 w-28">Total</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in rows" :key="row.key">
                        <tr class="border-b border-gray-50">
                            <td class="px-4 py-2">
                                <select :name="'items['+index+'][product_id]'" x-model="row.product_id" @change="onProduct(row)" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                                    <option value="">Select…</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                                <div class="mt-2" x-show="isPhone(row)" x-cloak>
                                    @include('supply.partials.imei-fields', [
                                        'mode' => "isPhone(row) ? 'pick' : ''",
                                        'options' => 'phonesHere(row)',
                                        'picked' => 'row.picked',
                                        'phones' => 'row.phones',
                                        'qty' => 'row.quantity',
                                        'pickName' => "'items[' + index + '][imeis][]'",
                                        'enterName' => "''",
                                    ])
                                </div>
                            </td>
                            <td class="px-2 py-2 text-center text-xs text-gray-500 align-top" x-text="available(row)"></td>
                            <td class="px-2 py-2 align-top"><input type="number" :name="'items['+index+'][quantity]'" x-model.number="row.quantity" min="1" :readonly="isPhone(row)" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" :name="'items['+index+'][unit_cost]'" x-model.number="row.unit_cost" min="0" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required></td>
                            <td class="px-2 py-2 text-right font-semibold" x-text="money(line(row))"></td>
                            <td class="px-2 py-2 text-center"><button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-red-500 text-lg">&times;</button></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50">
                        <td colspan="4" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Return total</td>
                        <td class="px-2 py-3 text-right text-sm font-bold" x-text="money(total())"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-lg font-bold">Submit purchase return</button>
    </form>
</x-supply-layout>
