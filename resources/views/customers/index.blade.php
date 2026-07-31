<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Customers') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">Online shoppers and in-store (POS) customers in one place.</p>
            </div>
            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Customer
            </a>
        </div>
    </x-slot>

    @php
        $customerRows = $customers->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'address' => $c->address,
                'points' => (int) ($c->reward_points ?? 0),
                'baki' => (float) ($c->baki_balance ?? 0),
                'baki_fmt' => number_format((float) ($c->baki_balance ?? 0), 2),
                'baki_url' => route('customers.baki.show', $c),
                'orders' => (int) ($c->orders_count ?? 0),
                'channel' => $c->user_id ? 'online' : 'offline',
                'joined' => optional($c->created_at)->format('M j, Y'),
                'initials' => strtoupper(mb_substr($c->name ?: 'CU', 0, 2)),
                'edit' => route('customers.edit', $c),
                'destroy' => route('customers.destroy', $c),
            ];
        })->values();
    @endphp

    <div class="py-6"
         x-data="customerDirectory({
            rows: @js($customerRows),
            onlineCount: {{ (int) $onlineCount }},
            offlineCount: {{ (int) $offlineCount }},
            csrf: @js(csrf_token()),
         })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    <span>{{ session('success') }}</span>
                    <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900">&times;</button>
                </div>
            @endif

            {{-- Summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button type="button" @click="tab = 'all'"
                        class="rounded-2xl border bg-white p-4 text-left shadow-sm transition"
                        :class="tab === 'all' ? 'border-indigo-300 ring-2 ring-indigo-100' : 'border-slate-200 hover:border-slate-300'">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">All customers</p>
                    <p class="mt-1 text-2xl font-black text-slate-900" x-text="rows.length">{{ $customers->count() }}</p>
                </button>
                <button type="button" @click="tab = 'online'"
                        class="rounded-2xl border bg-white p-4 text-left shadow-sm transition"
                        :class="tab === 'online' ? 'border-sky-300 ring-2 ring-sky-100' : 'border-slate-200 hover:border-slate-300'">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-sky-500">Online</p>
                    <p class="mt-1 text-2xl font-black text-slate-900" x-text="onlineCount">{{ $onlineCount }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">Website registered</p>
                </button>
                <button type="button" @click="tab = 'offline'"
                        class="rounded-2xl border bg-white p-4 text-left shadow-sm transition"
                        :class="tab === 'offline' ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200 hover:border-slate-300'">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Offline</p>
                    <p class="mt-1 text-2xl font-black text-slate-900" x-text="offlineCount">{{ $offlineCount }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-400">POS / walk-in</p>
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- Tabs + search — instant, no reload --}}
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200">
                        <button type="button" @click="tab = 'all'"
                                class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
                                :class="tab === 'all' ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-800'">
                            All <span class="ml-1 opacity-70" x-text="'(' + rows.length + ')'"></span>
                        </button>
                        <button type="button" @click="tab = 'online'"
                                class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
                                :class="tab === 'online' ? 'bg-sky-600 text-white shadow' : 'text-slate-500 hover:text-slate-800'">
                            Online <span class="ml-1 opacity-70" x-text="'(' + onlineCount + ')'"></span>
                        </button>
                        <button type="button" @click="tab = 'offline'"
                                class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition"
                                :class="tab === 'offline' ? 'bg-amber-500 text-white shadow' : 'text-slate-500 hover:text-slate-800'">
                            Offline <span class="ml-1 opacity-70" x-text="'(' + offlineCount + ')'"></span>
                        </button>
                    </div>

                    <div class="relative w-full sm:max-w-xs">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" x-model="q" placeholder="Search name, phone, email…"
                               class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-300 focus:ring-indigo-200">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Contact</th>
                                <th class="px-5 py-3 hidden md:table-cell">Address</th>
                                <th class="px-5 py-3 text-center">Orders</th>
                                <th class="px-5 py-3 text-center">Points</th>
                                <th class="px-5 py-3 text-right">Baki</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <template x-for="c in filtered" :key="c.id">
                                <tr class="group transition hover:bg-slate-50/80">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                                                 :class="c.channel === 'online' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-800'"
                                                 x-text="c.initials"></div>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-bold text-slate-900" x-text="c.name"></p>
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                                          :class="c.channel === 'online' ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'"
                                                          x-text="c.channel === 'online' ? 'Online' : 'Offline'"></span>
                                                </div>
                                                <p class="mt-0.5 text-[11px] text-slate-400">Joined <span x-text="c.joined"></span></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-semibold text-slate-800" x-text="c.phone || '—'"></p>
                                        <p class="text-[11px] text-slate-400" x-text="c.email || 'No email'"></p>
                                    </td>
                                    <td class="px-5 py-3.5 hidden md:table-cell">
                                        <p class="max-w-[220px] truncate text-xs text-slate-600" :title="c.address || ''" x-text="c.address || 'Not provided'"></p>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex min-w-[2rem] justify-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700" x-text="c.orders"></span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-100">
                                            ★ <span x-text="c.points"></span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <template x-if="c.baki > 0">
                                            <a :href="c.baki_url" class="inline-flex rounded-lg bg-orange-50 px-2.5 py-1 text-xs font-bold text-orange-800 ring-1 ring-orange-200 hover:bg-orange-100"
                                               x-text="'৳' + c.baki_fmt"></a>
                                        </template>
                                        <template x-if="c.baki <= 0">
                                            <span class="text-xs text-slate-300">—</span>
                                        </template>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="inline-flex items-center gap-1.5 opacity-90 group-hover:opacity-100">
                                            <a :href="c.edit"
                                               class="rounded-lg border border-indigo-100 bg-indigo-50 px-2.5 py-1.5 text-[11px] font-bold text-indigo-700 transition hover:bg-indigo-100">Edit</a>
                                            <button type="button"
                                                    @click="remove(c)"
                                                    class="rounded-lg border border-rose-100 bg-rose-50 px-2.5 py-1.5 text-[11px] font-bold text-rose-700 transition hover:bg-rose-100">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="filtered.length === 0" x-cloak class="px-6 py-16 text-center">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700" x-text="emptyTitle"></p>
                    <p class="mt-1 text-xs text-slate-400" x-text="emptyHint"></p>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-2.5 text-[11px] font-semibold text-slate-400">
                    <span>
                        Showing <span class="text-slate-700" x-text="filtered.length"></span>
                        of <span class="text-slate-700" x-text="rows.length"></span>
                    </span>
                    <span x-show="tab !== 'all'" x-cloak>
                        Filter:
                        <span class="text-slate-700" x-text="tab === 'online' ? 'Online only' : 'Offline only'"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
    function customerDirectory({ rows, onlineCount, offlineCount, csrf }) {
        return {
            rows: rows || [],
            onlineCount,
            offlineCount,
            csrf,
            tab: 'all',
            q: '',
            get filtered() {
                const q = this.q.trim().toLowerCase();
                return this.rows.filter((c) => {
                    if (this.tab === 'online' && c.channel !== 'online') return false;
                    if (this.tab === 'offline' && c.channel !== 'offline') return false;
                    if (!q) return true;
                    const hay = `${c.name || ''} ${c.phone || ''} ${c.email || ''} ${c.address || ''}`.toLowerCase();
                    return hay.includes(q);
                });
            },
            get emptyTitle() {
                if (this.q.trim()) return 'No matches';
                if (this.tab === 'online') return 'No online customers yet';
                if (this.tab === 'offline') return 'No offline customers yet';
                return 'No customers found';
            },
            get emptyHint() {
                if (this.q.trim()) return 'Try another name, phone, or email.';
                if (this.tab === 'online') return 'Customers who register on the website appear here.';
                if (this.tab === 'offline') return 'Walk-in POS customers appear here when you sell with a phone number.';
                return 'Add a customer or wait for website / POS sales.';
            },
            async remove(c) {
                const ok = await window.adminConfirm({
                    title: 'Delete?',
                    message: `Delete ${c.name}? This cannot be undone.`,
                    confirmText: 'Delete',
                    tone: 'danger',
                });
                if (!ok) return;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = c.destroy;
                form.innerHTML = `<input type="hidden" name="_token" value="${this.csrf}"><input type="hidden" name="_method" value="DELETE">`;
                document.body.appendChild(form);
                form.submit();
            },
        };
    }
    </script>
</x-app-layout>
