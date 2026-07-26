@php
    $activeMenu = $activeMenu ?? 'dashboard';
    $avatarUrl = auth()->user()->avatarUrl();
    $avatarText = auth()->user()->avatarInitials();
    $displayName = $customer?->name ?? auth()->user()->name;
    $menuClass = fn (string $key) => $activeMenu === $key
        ? 'flex items-center gap-2.5 rounded-xl bg-blue-50 px-3 py-2 text-[13px] font-semibold text-blue-700'
        : 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900';
@endphp
<aside class="space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-blue-100 text-sm font-extrabold text-blue-700">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" class="h-full w-full object-cover">
                @else
                    {{ $avatarText }}
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate text-[15px] font-bold text-slate-900">{{ $displayName }}</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                @if($customer?->phone)
                    <p class="mt-0.5 text-[11px] font-medium text-slate-600">{{ $customer->phone_country_code ? $customer->phone_country_code.' ' : '' }}{{ $customer->phone }}</p>
                @endif
            </div>
        </div>
        @if($activeMenu !== 'edit-profile')
            <a href="{{ route('website.account.profile.edit') }}" class="mt-3 inline-flex text-[11px] font-semibold text-blue-600 hover:text-blue-700">Edit Profile</a>
        @endif
        <div class="mt-4 border-t border-slate-100 pt-3">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Account Menu</p>
            <div class="space-y-1">
                <a href="{{ route('website.account') }}" class="{{ $menuClass('dashboard') }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span class="flex-1">Dashboard</span>
                </a>
                <a href="{{ route('website.account') }}#recent-orders" class="{{ $menuClass('orders') }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="flex-1">My Orders</span>
                </a>
                <a href="{{ route('website.track') }}" class="{{ $menuClass('track') }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="flex-1">Track Order</span>
                </a>
                <a href="{{ route('website.wishlist') }}" class="{{ $menuClass('wishlist') }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span class="flex-1">Wishlist</span>
                </a>
                <a href="{{ route('website.account.profile.edit') }}" class="{{ $menuClass('edit-profile') }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="flex-1">Edit Profile</span>
                </a>
                <form method="POST" action="{{ route('website.account.logout') }}" onsubmit="if (window.GagetLoader) window.GagetLoader.show('Signing out');">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-[13px] font-medium text-rose-600 hover:bg-rose-50">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <p class="text-[15px] font-bold text-slate-900">Need Help?</p>
                <p class="mt-1 text-[11px] text-slate-500">We’re here to help you.</p>
            </div>
        </div>
        <a href="{{ route('website.contact') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-[13px] font-semibold text-blue-700 hover:bg-slate-50">Contact Support</a>
    </div>
</aside>
