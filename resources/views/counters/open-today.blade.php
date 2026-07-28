<x-app-layout>
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">

            <div class="text-center mb-6">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Opening balance</h2>
                <p class="mt-1.5 text-sm text-slate-500">
                    @if(!empty($isAdminOpen))
                        Choose a free counter, enter opening cash, then sell on that till.
                    @else
                        Count the cash in your drawer, then start your day.
                    @endif
                </p>
            </div>

            @if(session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                @if(!empty($isAdminOpen))
                    <div class="px-5 py-4 bg-slate-50 border-b border-slate-100">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Admin POS till</p>
                        <p class="text-sm font-semibold text-slate-700 mt-0.5">Only free counters are listed. Busy tills stay with their cashier.</p>
                    </div>

                    @if(($freeCounters ?? collect())->isEmpty())
                        <div class="p-5 space-y-3">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 font-medium">
                                No free counters right now. Wait until a till is closed, or ask staff to finish their session.
                            </div>
                            <a href="{{ route('counters.sessions.index') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold py-3 transition">
                                View cash sessions
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('counters.sessions.open-today.store') }}" class="p-5 space-y-4">
                            @csrf
                            <div>
                                <label for="counter_id" class="block text-xs font-bold text-slate-500 mb-1.5">Free counter *</label>
                                <select id="counter_id" name="counter_id" required
                                        class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-bold text-slate-900">
                                    @foreach($freeCounters as $free)
                                        <option value="{{ $free->id }}" @selected((string) old('counter_id', $counter?->id) === (string) $free->id)>{{ $free->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="opening_cash" class="block text-xs font-bold text-slate-500 mb-1.5">Opening cash in drawer (৳) *</label>
                                <input id="opening_cash" type="number" name="opening_cash" step="0.01" min="0" required autofocus
                                       value="{{ old('opening_cash', '0') }}"
                                       class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-lg py-3 px-4 font-black text-slate-900">
                                <p class="mt-1.5 text-xs text-slate-500">Enter the exact cash amount before your first sale.</p>
                            </div>
                            <div>
                                <label for="notes" class="block text-xs font-bold text-slate-500 mb-1.5">Notes</label>
                                <input id="notes" type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional"
                                       class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-3 shadow-sm transition">
                                Open free counter &amp; start POS
                            </button>
                        </form>
                    @endif
                @else
                <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Your counter</p>
                        <p class="text-base font-bold text-slate-900">{{ $counter->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Date</p>
                        <p class="text-sm font-semibold text-slate-700">{{ now()->format('M j, Y') }}</p>
                    </div>
                </div>

                @if($staleSession)
                    <div class="p-5 space-y-4">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 font-medium">
                            A session from <strong>{{ $staleSession->opened_at->format('M j, g:i A') }}</strong> is still open.
                            Close it first, then enter today&rsquo;s opening cash.
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Started with</p>
                                <p class="font-bold text-slate-800">৳{{ number_format($staleSession->opening_cash, 2) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                                <p class="text-[11px] font-bold text-slate-400 uppercase">Expected now</p>
                                <p class="font-bold text-emerald-700">৳{{ number_format($expected, 2) }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('counters.sessions.close', $staleSession) }}" class="space-y-3"
                              onsubmit="return confirm('Confirm you counted the drawer. Enter the real amount — expected is only a guide.');">
                            @csrf
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-900">
                                Count the cash yourself. Expected ৳{{ number_format($expected, 2) }} is a guide only.
                            </div>
                            @include('counters.partials.transfer-log', ['transferLog' => $transferLog ?? [], 'class' => ''])
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1.5">Counted cash in drawer (৳) *</label>
                                <input type="number" name="closing_cash" step="0.01" min="0" required value="{{ old('closing_cash') }}"
                                       placeholder="Enter counted amount" autocomplete="off"
                                       class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1.5">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Required if amount ≠ expected"
                                       class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                            </div>
                            <label class="flex items-start gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="counted_confirm" value="1" required class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>I physically counted the drawer.</span>
                            </label>
                            <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold py-3 transition">
                                Close previous session
                            </button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('counters.sessions.open-today.store') }}" class="p-5 space-y-4">
                        @csrf
                        <div>
                            <label for="opening_cash" class="block text-xs font-bold text-slate-500 mb-1.5">Opening cash in drawer (৳) *</label>
                            <input id="opening_cash" type="number" name="opening_cash" step="0.01" min="0" required autofocus
                                   value="{{ old('opening_cash', '0') }}"
                                   class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-lg py-3 px-4 font-black text-slate-900">
                            <p class="mt-1.5 text-xs text-slate-500">Enter the exact cash amount before your first sale today.</p>
                        </div>
                        <div>
                            <label for="notes" class="block text-xs font-bold text-slate-500 mb-1.5">Notes</label>
                            <input id="notes" type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional"
                                   class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-3 shadow-sm transition">
                            Start day &amp; open POS
                        </button>
                    </form>
                @endif
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-slate-400 hover:text-slate-600">Sign out</button>
            </form>
        </div>
    </div>
</x-app-layout>
