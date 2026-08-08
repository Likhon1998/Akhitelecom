@extends('website.layout')

@section('title', 'My Account — ' . ($settings->store_name ?? 'Store'))

@section('content')
@php
    $currency = $settings->currency_symbol ?? 'Tk';
    $firstName = explode(' ', trim($customer?->name ?? auth()->user()->name ?? 'Customer'))[0] ?? 'Customer';
    $justPlacedInvoice = request('order');
    $justPlacedOid = request('oid');
    $justPlaced = request()->boolean('placed');
    $highlightOrderId = null;
    if ($justPlaced && ($recentOrders ?? collect())->isNotEmpty()) {
        $match = ($recentOrders ?? collect())->first(function ($order) use ($justPlacedInvoice, $justPlacedOid) {
            if ($justPlacedInvoice && (string) $order->invoice_no === (string) $justPlacedInvoice) {
                return true;
            }
            if ($justPlacedOid && (int) $order->id === (int) $justPlacedOid) {
                return true;
            }

            return false;
        }) ?? ($recentOrders ?? collect())->first();
        $highlightOrderId = $match?->id;
        $justPlacedInvoice = $justPlacedInvoice ?: ($match?->invoice_no);
    }
    $orderDetailsMap = ($recentOrders ?? collect())->mapWithKeys(function ($order) use ($orderTracking, $customer) {
        $track = $orderTracking[$order->id] ?? null;

        return [
            $order->id => [
                'id' => $order->id,
                'invoice' => $order->invoice_no,
                'date' => asian_date($order->created_at, 'M j, Y'),
                'datetime' => asian_datetime($order->created_at, 'M j, Y g:i A'),
                'status' => $order->status,
                'status_label' => $track['status_label'] ?? ucfirst($order->status),
                'total' => number_format((float) $order->total_amount, 2),
                'where' => $track['where_is_product'] ?? '',
                'courier' => $order->shipping_courier,
                'tracking_no' => $order->shipping_tracking_no,
                'address' => $customer?->address ?: 'No address saved yet.',
                'timeline' => $track['timeline'] ?? [],
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product?->name ?? 'Product',
                    'qty' => (int) $item->quantity,
                    'subtotal' => number_format((float) $item->subtotal, 2),
                ])->values()->all(),
            ],
        ];
    });
@endphp
<div class="max-w-[1280px] mx-auto px-4 md:px-5 py-6"
     x-data="{
        openOrder: {{ $highlightOrderId ?? $activeOrder?->id ?? 'null' }},
        detailOpen: false,
        detail: null,
        orders: @js($orderDetailsMap),
        openDetail(id) {
            this.detail = this.orders[id] || null;
            this.detailOpen = !!this.detail;
        },
        closeDetail() {
            this.detailOpen = false;
            this.detail = null;
        },
        statusClass(status) {
            if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
            if (status === 'shipped') return 'bg-sky-100 text-sky-800';
            if (status === 'processing') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-300';
            if (status === 'pending') return 'bg-orange-100 text-orange-800';
            if (['cancelled','returned','refunded'].includes(status)) return 'bg-rose-100 text-rose-700';
            return 'bg-slate-100 text-slate-700';
        },
        flowSteps: [
            { key: 'pending', label: 'Order received' },
            { key: 'processing', label: 'Packaging' },
            { key: 'shipped', label: 'Out for delivery' },
            { key: 'completed', label: 'Delivered' },
        ],
        buildTrack(order) {
            if (!order) return [];
            const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
            const status = order.status || 'pending';
            const rank = Object.prototype.hasOwnProperty.call(rankMap, status) ? rankMap[status] : 0;
            const byKey = {};
            (order.timeline || []).forEach((s) => { if (s && s.key) byKey[s.key] = s; });
            return this.flowSteps.map((step, i) => {
                const log = byKey[step.key] || {};
                const active = step.key === status;
                const passed = i < rank || status === 'completed';
                return {
                    key: step.key,
                    label: step.label,
                    active,
                    done: passed && !active,
                    at: log.at || null,
                    note: log.note || null,
                };
            });
        },
        progressPct(order) {
            if (!order) return 0;
            const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
            const rank = rankMap[order.status] ?? 0;
            return (rank / 3) * 100;
        },
     }"
     x-init="
        @if($highlightOrderId)
            openDetail({{ (int) $highlightOrderId }});
            setTimeout(() => {
                document.getElementById('recent-orders')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 120);
        @endif
     "
     @keydown.escape.window="closeDetail()">
    <div class="acct-page max-w-[1280px] mx-auto px-3 sm:px-4 md:px-5 py-4 sm:py-6">
    <div class="grid gap-4 xl:grid-cols-[250px_minmax(0,1fr)] min-w-0">
        <div class="space-y-4 min-w-0 order-2 xl:order-1">
            @include('website.partials.account-sidebar', ['activeMenu' => 'dashboard', 'customer' => $customer, 'activeOrder' => $activeOrder])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hidden sm:block">
                <p class="text-[15px] font-bold text-slate-900">Exclusive Member Benefits</p>
                <p class="mt-1 text-[11px] text-slate-500">Get special offers and faster checkout.</p>
                <a href="{{ route('website.shop') }}" class="mt-3 inline-flex text-[13px] font-semibold text-blue-600 hover:text-blue-700">Explore Benefits →</a>
            </div>
        </div>

        <section class="space-y-4 min-w-0 order-1 xl:order-2">
            @if(session('profile_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] font-semibold text-emerald-700">
                    {{ session('profile_success') }}
                </div>
            @endif

            @if($justPlaced)
                <div class="gaget-order-placed-banner" role="status">
                    <div class="gaget-order-placed-banner__icon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="gaget-order-placed-banner__title">Order placed successfully</p>
                        <p class="gaget-order-placed-banner__text">
                            @if($justPlacedInvoice)
                                Your order <strong>{{ $justPlacedInvoice }}</strong> is confirmed. Track it below — we’ll update status as it moves.
                            @else
                                Your order is confirmed. Track it below — we’ll update status as it moves.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="min-w-0">
                <h1 class="acct-welcome-title">Welcome back, {{ $firstName }}!</h1>
                <p class="mt-0.5 text-[13px] text-slate-500">Here’s what’s happening with your account today.</p>
            </div>

            <div class="gaget-account-stats">
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Total Orders</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $totalOrders }}</p>
                            <p class="gaget-account-stat__hint">All time</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--blue">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Packaging</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $packagingOrders }}</p>
                            <p class="gaget-account-stat__hint">Received / packing</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--amber">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">In Transit</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $inTransitOrders }}</p>
                            <p class="gaget-account-stat__hint">Out for delivery</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--green">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17h6m-8 0H5a2 2 0 01-2-2V7a2 2 0 012-2h9a2 2 0 012 2v2m0 8h1a2 2 0 002-2v-3m-3 5a2 2 0 11-4 0m4 0a2 2 0 104 0m-4 0H9m10-8l-2-3h-3"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Delivered</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $deliveredOrders }}</p>
                            <p class="gaget-account-stat__hint">Completed</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--lime">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.52fr)_minmax(0,340px)] min-w-0">
                <div class="space-y-4 min-w-0">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0"
                         x-data="{
                            slides: @js($activeOrderSlides ?? []),
                            index: 0,
                            touchStartX: null,
                            flowSteps: [
                                { key: 'pending', label: 'Order received' },
                                { key: 'processing', label: 'Packaging' },
                                { key: 'shipped', label: 'Out for delivery' },
                                { key: 'completed', label: 'Delivered' },
                            ],
                            get current() { return this.slides[this.index] || null; },
                            get count() { return this.slides.length; },
                            get track() { return this.buildTrack(this.current); },
                            get fillPct() { return this.progressPct(this.current); },
                            buildTrack(order) {
                                if (!order) return [];
                                const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
                                const status = order.status || 'pending';
                                const rank = Object.prototype.hasOwnProperty.call(rankMap, status) ? rankMap[status] : 0;
                                const byKey = {};
                                (order.timeline || []).forEach((s) => { if (s && s.key) byKey[s.key] = s; });
                                return this.flowSteps.map((step, i) => {
                                    const log = byKey[step.key] || {};
                                    const active = step.key === status;
                                    const passed = i < rank || status === 'completed';
                                    return {
                                        key: step.key,
                                        label: step.label,
                                        active,
                                        done: passed && !active,
                                        at: log.at || null,
                                        note: log.note || null,
                                    };
                                });
                            },
                            progressPct(order) {
                                if (!order) return 0;
                                const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
                                const rank = rankMap[order.status] ?? 0;
                                return (rank / 3) * 100;
                            },
                            prev() { if (this.count < 2) return; this.index = (this.index - 1 + this.count) % this.count; },
                            next() { if (this.count < 2) return; this.index = (this.index + 1) % this.count; },
                            go(i) { this.index = i; },
                            onTouchStart(e) { this.touchStartX = e.changedTouches?.[0]?.clientX ?? null; },
                            onTouchEnd(e) {
                                if (this.touchStartX === null || this.count < 2) return;
                                const dx = (e.changedTouches?.[0]?.clientX ?? this.touchStartX) - this.touchStartX;
                                if (Math.abs(dx) < 40) return;
                                dx < 0 ? this.next() : this.prev();
                                this.touchStartX = null;
                            },
                            statusClass(status) {
                                if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
                                if (status === 'shipped') return 'bg-sky-100 text-sky-800';
                                if (status === 'processing') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-300';
                                if (status === 'pending') return 'bg-orange-100 text-orange-800';
                                if (status === 'cancelled' || status === 'returned' || status === 'refunded') return 'bg-rose-100 text-rose-700';
                                return 'bg-slate-100 text-slate-700';
                            }
                         }">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[15px] font-bold text-slate-900">
                                Active Order<span x-show="count > 1" x-cloak x-text="'s (' + count + ')'"></span>
                            </h2>
                            <p class="text-[11px] text-slate-500">Live status from our store to your doorstep.</p>
                        </div>
                        <a href="{{ route('website.account') }}#recent-orders" class="text-[11px] font-bold text-blue-600 hover:text-blue-700">View All Orders →</a>
                    </div>

                    <template x-if="current">
                        <div class="relative"
                             @touchstart.passive="onTouchStart($event)"
                             @touchend.passive="onTouchEnd($event)">
                            <div class="rounded-2xl border border-slate-100 bg-white">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[13px] font-bold text-slate-900" x-text="current.invoice"></p>
                                        <p class="text-[11px] text-slate-500" x-show="current.id" x-text="'Order ID · Ref #' + current.id"></p>
                                        <p class="text-[11px] text-slate-500" x-text="'Placed on ' + current.date"></p>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[12px] font-extrabold"
                                          :class="statusClass(current.status)">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse" x-show="['pending','processing','shipped'].includes(current.status)"></span>
                                        <span x-text="current.status_label"></span>
                                    </span>
                                </div>

                                {{-- Connected 4-step progress line --}}
                                <div class="gaget-progress mt-6" aria-label="Order progress">
                                    <div class="gaget-progress__rail" aria-hidden="true">
                                        <div class="gaget-progress__fill" :style="'width:' + fillPct + '%'"></div>
                                    </div>
                                    <div class="gaget-progress__steps">
                                        <template x-for="(step, sIdx) in track" :key="current.id + '-track-' + step.key">
                                            <div class="gaget-progress__step"
                                                 :class="{
                                                    'is-done': step.done,
                                                    'is-active': step.active,
                                                    'is-waiting': !step.done && !step.active
                                                 }">
                                                <div class="gaget-progress__dot" x-text="step.done || (step.active && current.status === 'completed') ? '✓' : (sIdx + 1)"></div>
                                                <p class="gaget-progress__label" x-text="step.label"></p>
                                                <p class="gaget-progress__meta" x-text="step.active ? (step.at || 'In progress') : (step.at || 'Waiting')"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5 text-left"
                                     x-show="current.where">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-amber-700">Where is my order?</p>
                                    <p class="mt-1 text-[13px] font-semibold text-amber-950" x-text="current.where"></p>
                                </div>

                                <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white text-xl">📦</div>
                                        <div class="min-w-0">
                                            <p class="truncate text-[13px] font-bold text-slate-900">
                                                <span x-text="current.item_name"></span>
                                                <span x-show="current.extra_items > 0" x-text="' +' + current.extra_items + ' more'"></span>
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-slate-500" x-text="'Qty: ' + current.qty"></p>
                                        </div>
                                    </div>
                                    <a href="{{ route('website.account') }}#recent-orders" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-blue-700 hover:bg-blue-50">View Orders</a>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Current Location</p>
                                        <p class="mt-1 text-[13px] font-semibold text-slate-800" x-text="current.courier"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Delivery Address</p>
                                        <p class="mt-1 line-clamp-2 text-[13px] font-semibold text-slate-800" x-text="current.address"></p>
                                    </div>
                                </div>
                            </div>

                            <template x-if="count > 1">
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <button type="button" @click="prev()"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                            aria-label="Previous order">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <template x-for="(slide, i) in slides" :key="'dot-' + slide.id">
                                            <button type="button" @click="go(i)"
                                                    class="h-2 rounded-full transition-all"
                                                    :class="i === index ? 'w-5 bg-blue-600' : 'w-2 bg-slate-300 hover:bg-slate-400'"
                                                    :aria-label="'Show order ' + (i + 1)"></button>
                                        </template>
                                        <span class="ml-1 text-[11px] font-semibold text-slate-500" x-text="(index + 1) + ' / ' + count"></span>
                                    </div>
                                    <button type="button" @click="next()"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                            aria-label="Next order">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div x-show="!current" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-[13px] font-semibold text-slate-700">No active order yet.</p>
                        <p class="mt-1 text-xs text-slate-500">Place your first order and status updates will appear here.</p>
                        <a href="{{ route('website.shop') }}" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-[13px] font-semibold text-white hover:bg-blue-700">Start Shopping</a>
                    </div>
                    </div>

                    <div id="recent-orders" class="acct-orders rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                <div class="mb-3 flex items-center justify-between gap-3 min-w-0">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-bold text-slate-900">Recent Orders</h2>
                    </div>
                    <a href="#recent-orders" class="shrink-0 text-[11px] font-bold text-blue-600 hover:text-blue-700">View all</a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <p class="text-[13px] font-semibold text-slate-700">No orders yet.</p>
                        <p class="mt-1 text-xs text-slate-500">When you place a website order, it will appear here immediately.</p>
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="acct-order-cards space-y-3 md:hidden">
                        @foreach($recentOrders as $order)
                            @php $track = $orderTracking[$order->id] ?? null; @endphp
                            <div class="acct-order-card {{ $highlightOrderId && (int) $highlightOrderId === (int) $order->id ? 'gaget-order-row-highlight' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-slate-900 break-all">{{ $order->invoice_no }}</p>
                                        <p class="text-[10px] text-slate-400">#{{ $order->id }} · {{ asian_date($order->created_at, 'M j, Y') }}</p>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-extrabold
                                        @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                                        @elseif($order->status === 'shipped') bg-sky-100 text-sky-800
                                        @elseif($order->status === 'processing') bg-amber-100 text-amber-800
                                        @elseif($order->status === 'pending') bg-orange-100 text-orange-800
                                        @elseif(in_array($order->status, ['cancelled', 'returned', 'refunded'])) bg-rose-100 text-rose-700
                                        @else bg-slate-100 text-slate-700 @endif">
                                        {{ $track['status_label'] ?? ucfirst($order->status) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-[12px] font-medium text-slate-700 break-words">
                                    {{ $order->items->first()?->product?->name ?? 'Product' }}
                                    <span class="text-slate-400">· {{ $order->items->sum('quantity') }} item(s)</span>
                                </p>
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <p class="text-[14px] font-extrabold text-slate-900">{{ $currency }}{{ number_format($order->total_amount, 2) }}</p>
                                    <button type="button"
                                            @click="openDetail({{ $order->id }})"
                                            class="shrink-0 inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden md:block overflow-x-auto max-w-full">
                        <table class="w-full text-[13px]">
                            <thead class="border-b border-slate-100 text-[10px] uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-2 py-2.5 text-left">Order ID</th>
                                    <th class="px-2 py-2.5 text-left">Date</th>
                                    <th class="px-2 py-2.5 text-left">Items</th>
                                    <th class="px-2 py-2.5 text-right">Total</th>
                                    <th class="px-2 py-2.5 text-left">Status</th>
                                    <th class="px-2 py-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($recentOrders as $order)
                                    @php $track = $orderTracking[$order->id] ?? null; @endphp
                                    <tr class="{{ $highlightOrderId && (int) $highlightOrderId === (int) $order->id ? 'gaget-order-row-highlight' : '' }}">
                                        <td class="px-2 py-3 font-semibold text-slate-900">
                                            <span class="block break-all">{{ $order->invoice_no }}</span>
                                            <span class="text-[10px] font-medium text-slate-400">#{{ $order->id }}</span>
                                        </td>
                                        <td class="px-2 py-3 text-slate-600 whitespace-nowrap">{{ asian_date($order->created_at, 'M j, Y') }}</td>
                                        <td class="px-2 py-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-base">📦</div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium text-slate-800">{{ $order->items->first()?->product?->name ?? 'Product' }}</p>
                                                    <p class="text-[11px] text-slate-500">{{ $order->items->sum('quantity') }} item(s)</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3 text-right font-bold text-slate-900 whitespace-nowrap">{{ $currency }}{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="px-2 py-3">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-extrabold
                                                @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                                                @elseif($order->status === 'shipped') bg-sky-100 text-sky-800
                                                @elseif($order->status === 'processing') bg-amber-100 text-amber-800 ring-1 ring-amber-300
                                                @elseif($order->status === 'pending') bg-orange-100 text-orange-800
                                                @elseif(in_array($order->status, ['cancelled', 'returned', 'refunded'])) bg-rose-100 text-rose-700
                                                @else bg-slate-100 text-slate-700 @endif">
                                                {{ $track['status_label'] ?? ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-3 text-right">
                                            <button type="button"
                                                    @click="openDetail({{ $order->id }})"
                                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
                </div>

                <div class="space-y-4 min-w-0">
                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <h2 class="text-[15px] font-bold text-slate-900">Account Overview</h2>
                        <div class="mt-3 space-y-3 text-[13px]">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Name</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-words">{{ $customer?->name ?? auth()->user()->name }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Email</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-all">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Phone</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-all">{{ $customer?->phone ?: 'Not added yet' }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Member Since</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $memberSince ?? now()->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('website.account.profile.edit') }}" class="acct-info-btn mt-4">View Account Details</a>
                    </div>

                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-2 min-w-0">
                            <h2 class="text-[15px] font-bold text-slate-900">Shipping Addresses</h2>
                            <span class="shrink-0 text-[11px] font-semibold text-blue-600">+ Add</span>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 min-w-0">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[13px] font-bold text-slate-900">Home</p>
                                <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Default</span>
                            </div>
                            <p class="text-[13px] text-slate-700 break-words whitespace-normal">{{ $customer?->address ?: 'No address saved yet. It will be saved on your next checkout.' }}</p>
                            @if($customer?->phone)
                                <p class="mt-2 text-[11px] font-medium text-slate-600 break-all">{{ $customer->phone }}</p>
                            @endif
                        </div>
                        <button type="button" class="acct-info-btn mt-4">View All Addresses</button>
                    </div>

                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-2 min-w-0">
                            <h2 class="text-[15px] font-bold text-slate-900">Payment Methods</h2>
                            <span class="shrink-0 text-[11px] font-semibold text-blue-600">+ Add</span>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 min-w-0">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[13px] font-bold text-slate-900">Cash on Delivery</p>
                                <span class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">Active</span>
                            </div>
                            <p class="text-[11px] text-slate-500 break-words whitespace-normal">Used for current website checkout and synced with admin order updates.</p>
                        </div>
                        <button type="button" class="acct-info-btn mt-4">View All Payment Methods</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Order details modal --}}
    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="closeDetail()">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeDetail()"></div>
        <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl"
             @click.outside="closeDetail()"
             x-show="detailOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            <template x-if="detail">
                <div>
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Order ID</p>
                            <h3 class="mt-0.5 truncate text-[18px] font-extrabold text-slate-900" x-text="detail.invoice"></h3>
                            <p class="text-[11px] text-slate-500" x-show="detail.id" x-text="'Ref #' + detail.id"></p>
                            <p class="mt-0.5 text-[12px] text-slate-500" x-text="'Placed ' + detail.datetime"></p>
                        </div>
                        <button type="button" @click="closeDetail()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold" :class="statusClass(detail.status)" x-text="detail.status_label"></span>
                            <p class="text-[15px] font-extrabold text-slate-900" x-text="'{{ $currency }}' + detail.total"></p>
                        </div>

                        <p class="rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5 text-[12px] font-medium text-blue-800" x-text="detail.where" x-show="detail.where"></p>

                        <div class="rounded-xl border border-slate-100 bg-white p-3">
                            <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-400">Tracking</p>
                            <div class="gaget-progress" aria-label="Order progress">
                                <div class="gaget-progress__rail" aria-hidden="true">
                                    <div class="gaget-progress__fill" :style="'width:' + progressPct(detail) + '%'"></div>
                                </div>
                                <div class="gaget-progress__steps">
                                    <template x-for="(step, sIdx) in buildTrack(detail)" :key="'detail-' + step.key">
                                        <div class="gaget-progress__step"
                                             :class="{ 'is-done': step.done, 'is-active': step.active, 'is-waiting': !step.done && !step.active }">
                                            <div class="gaget-progress__dot" x-text="step.done || (step.active && detail.status === 'completed') ? '✓' : (sIdx + 1)"></div>
                                            <p class="gaget-progress__label" x-text="step.label"></p>
                                            <p class="gaget-progress__meta" x-text="step.active ? (step.at || 'In progress') : (step.at || 'Waiting')"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">Items</p>
                            <div class="divide-y divide-slate-100 rounded-xl border border-slate-100">
                                <template x-for="(item, i) in detail.items" :key="'item-' + i">
                                    <div class="flex items-start justify-between gap-3 px-3 py-2.5 text-[13px]">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900" x-text="item.name"></p>
                                            <p class="text-[11px] text-slate-500" x-text="'Qty ' + item.qty"></p>
                                        </div>
                                        <p class="shrink-0 font-bold text-slate-900" x-text="'{{ $currency }}' + item.subtotal"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Courier</p>
                                <p class="mt-1 text-[12px] font-semibold text-slate-800" x-text="detail.courier || 'Our store / packing desk'"></p>
                                <p class="mt-0.5 font-mono text-[11px] text-slate-500" x-show="detail.tracking_no" x-text="detail.tracking_no"></p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Delivery address</p>
                                <p class="mt-1 line-clamp-3 text-[12px] font-semibold text-slate-800" x-text="detail.address"></p>
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 flex flex-wrap gap-2 border-t border-slate-100 bg-white px-5 py-3">
                        <button type="button" @click="closeDetail()" class="gaget-btn-primary flex-1 text-[13px] py-2.5">Close</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
    </div>
</div>
@endsection
