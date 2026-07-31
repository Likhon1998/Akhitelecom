<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customer->name }} — Baki</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $customer->phone ?: 'No mobile' }} · Full credit history</p>
            </div>
            <a href="{{ route('customers.baki.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Current baki</p>
                    <p class="mt-1 text-3xl font-black text-amber-900">৳{{ number_format((float) $customer->baki_balance, 2) }}</p>
                </div>

                @if((float) $customer->baki_balance > 0)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Collect payment</p>
                    <form method="POST" action="{{ route('customers.baki.pay', $customer) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   max="{{ (float) $customer->baki_balance }}"
                                   value="{{ old('amount', number_format((float) $customer->baki_balance, 2, '.', '')) }}"
                                   class="w-full rounded-xl border-slate-200 text-sm font-semibold" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Method</label>
                            <select name="method" class="w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="cash" @selected(old('method') === 'cash')>Cash</option>
                                <option value="card" @selected(old('method') === 'card')>Card</option>
                                <option value="bkash" @selected(old('method') === 'bkash')>bKash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Note (optional)</label>
                            <input type="text" name="note" value="{{ old('note') }}" maxlength="255"
                                   class="w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Partial payment">
                        </div>
                        <button type="submit"
                                class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                            Collect payment
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Payment & credit history</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">When</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Invoice / note</th>
                            <th class="px-4 py-3">By</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($entries as $entry)
                            @php
                                $amt = (float) $entry->amount;
                                $isCredit = $amt > 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $entry->created_at?->timezone(config('app.display_timezone', 'Asia/Dhaka'))->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                        {{ $entry->type === 'sale' ? 'bg-amber-100 text-amber-800' : ($entry->type === 'payment' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $entry->type }}
                                    </span>
                                    @if($entry->method)
                                        <span class="ml-1 text-[10px] text-slate-400">{{ $entry->method }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if($entry->order)
                                        <a href="{{ route('pos.receipt', $entry->order) }}" class="font-semibold text-indigo-600 hover:underline" target="_blank">
                                            {{ $entry->order->invoice_no }}
                                        </a>
                                    @endif
                                    @if($entry->note)
                                        <div class="text-xs text-slate-500">{{ $entry->note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $entry->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $isCredit ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ $isCredit ? '+' : '' }}৳{{ number_format($amt, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500">No baki entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($entries->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
