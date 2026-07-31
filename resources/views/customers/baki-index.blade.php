<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customer Baki</h2>
                <p class="mt-0.5 text-sm text-slate-500">Outstanding credit (pay later) balances from POS sales.</p>
            </div>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                All customers
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
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

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Total outstanding</p>
                <p class="mt-1 text-3xl font-black text-amber-900">৳{{ number_format($totalOutstanding, 2) }}</p>
                <p class="mt-1 text-xs text-amber-700/80">{{ $customers->count() }} customer(s) with baki</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Mobile</th>
                            <th class="px-4 py-3 text-right">Baki balance</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($customers as $customer)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-amber-700">৳{{ number_format((float) $customer->baki_balance, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('customers.baki.show', $customer) }}"
                                       class="inline-flex rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">
                                        History / Pay
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">No customers with outstanding baki.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
