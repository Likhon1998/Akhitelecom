<div x-show="sidebarOpen"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-40 bg-gray-900/80 lg:hidden"
     @click="sidebarOpen = false"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-[min(260px,88vw)] bg-[#0B1220] text-slate-300 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:w-[260px] flex flex-col border-r border-white/5">

    <div class="flex items-center gap-2.5 h-[4.25rem] px-5 shrink-0 border-b border-white/5">
        @php
            $navSettings = \App\Models\SiteSetting::current();
            $navName = $navSettings->store_name
                ?: (Auth::user()->shop->name ?? config('app.name', 'Akhi Telecom'));
            $navIcon = $navSettings->favicon_path
                ? public_storage_url($navSettings->favicon_path)
                : ($navSettings->logo_path ? public_storage_url($navSettings->logo_path) : null);
        @endphp
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-2.5 text-white font-bold text-[15px] tracking-tight min-w-0 flex-1">
            @if($navIcon)
                <img src="{{ $navIcon }}?v={{ @filemtime(storage_path('app/public/'.($navSettings->favicon_path ?: $navSettings->logo_path))) ?: time() }}" alt="" class="h-11 w-11 object-contain shrink-0" width="44" height="44" style="width:44px;height:44px;object-fit:contain;background:transparent;border:0;">
                <span class="truncate">{{ $navName }}</span>
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
                <span class="truncate">{{ $navName }}</span>
            @endif
        </a>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white" aria-label="Close menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="admin-scroll-hide p-3 space-y-0.5 flex-1 overflow-y-auto"
         @click="if ($event.target.closest('a')) sidebarOpen = false"
         x-data="{ inventoryOpen: {{ request()->routeIs('brands.*', 'categories.*', 'products.*') && !request()->routeIs('supply.*', 'stock.*') ? 'true' : 'false' }}, supplyOpen: {{ request()->routeIs('supply.*', 'stock.*', 'reports.low_stock') ? 'true' : 'false' }}, cmsOpen: {{ request()->routeIs('cms.*') ? 'true' : 'false' }} }">

        @can('view dashboard')
        <a href="{{ route('dashboard') }}"
           class="{{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2 rounded-xl transition-all font-medium text-[13px]">
            <svg class="w-4.5 h-4.5 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-500' }}" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>
        @endcan

        @can('manage inventory')
        <div class="pt-2">
            <button @click="inventoryOpen = !inventoryOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all font-medium text-sm {{ request()->routeIs('brands.*', 'categories.*', 'products.*') && !request()->routeIs('supply.*', 'stock.*') ? 'text-white bg-white/10' : 'hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('brands.*', 'categories.*', 'products.*') && !request()->routeIs('supply.*', 'stock.*') ? 'text-blue-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Inventory</span>
                </div>
                <svg :class="inventoryOpen ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div x-show="inventoryOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">

                <a href="{{ route('brands.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('brands.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Brand</a>
                <a href="{{ route('categories.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('categories.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Category</a>
                <a href="{{ route('products.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('products.index', 'products.edit') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Product List</a>
                <a href="{{ route('products.create') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('products.create') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Add Product</a>
                <a href="{{ route('products.import') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('products.import*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Add CSV Product</a>
                <a href="{{ route('products.barcodes') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('products.barcodes*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Print Bar Code</a>
            </div>
        </div>

        <div class="pt-2">
            <button @click="supplyOpen = !supplyOpen" 
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all font-medium text-sm {{ request()->routeIs('supply.*', 'stock.*', 'reports.low_stock') ? 'text-white bg-white/10' : 'hover:bg-white/5 hover:text-white' }}">
                    <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('supply.*', 'stock.*', 'reports.low_stock') ? 'text-blue-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Stock & Supply</span>
                </div>
                <svg :class="supplyOpen ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div x-show="supplyOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">

                <a href="{{ route('supply.opening-inventory.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.opening-inventory.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Opening Inventory</a>
                <a href="{{ route('supply.purchase-orders.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.purchase-orders.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Purchase Order</a>
                <a href="{{ route('supply.reorder-levels.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.reorder-levels.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Reorder Level</a>
                <a href="{{ route('reports.low_stock') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('reports.low_stock') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Low Stock Report</a>
                <a href="{{ route('supply.purchase-returns.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.purchase-returns.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Purchase Return</a>
                <a href="{{ route('supply.adjustments.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.adjustments.*', 'stock.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Stock Adjustment</a>
                <a href="{{ route('supply.stock-transfers.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.stock-transfers.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Stock Transfer</a>
                <a href="{{ route('supply.damage-products.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.damage-products.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Damage Product</a>
                <a href="{{ route('supply.stores.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.stores.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Store Settings</a>
                <a href="{{ route('supply.warehouses.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.warehouses.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Warehouse</a>
                <a href="{{ route('supply.suppliers.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('supply.suppliers.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Supplier</a>
            </div>
        </div>
        @endcan

        @can('manage website')
        <div class="pt-2">
            <button @click="cmsOpen = !cmsOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all font-medium text-sm {{ request()->routeIs('cms.*') ? 'text-white bg-white/10' : 'hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('cms.*') ? 'text-blue-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    <span>CMS</span>
                </div>
                <svg :class="cmsOpen ? 'rotate-180' : ''" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="cmsOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
                <a href="{{ route('cms.landing.edit') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.landing.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Landing Page</a>
                <a href="{{ route('cms.pages.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.pages.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Pages</a>
                <a href="{{ route('cms.blogs.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.blogs.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Blogs</a>
                <a href="{{ route('cms.blog-categories.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.blog-categories.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Blog Categories</a>
                <a href="{{ route('cms.faqs.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.faqs.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">FAQ</a>
                <a href="{{ route('cms.faq-categories.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.faq-categories.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">FAQ Categories</a>
                <a href="{{ route('cms.contact.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.contact.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Contact</a>
                <a href="{{ route('cms.slides.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.slides.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Home Slide</a>
                <a href="{{ route('cms.reviews.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('cms.reviews.*') ? 'text-white bg-blue-500/15' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Reviews</a>
            </div>
        </div>
        @endcan

       @can('view sales ledger')
        <a href="{{ route('sales.index') }}" 
           class="{{ request()->routeIs('sales.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('sales.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Sales Ledger
        </a>

        @if(Auth::user()->isAdminUser())
        @php
            $pendingWebOrders = \App\Models\Order::where('shop_id', Auth::user()->shop_id)
                                ->onlineOrders()
                                ->where('status', 'pending')
                                ->count();
        @endphp
        <a href="{{ route('online-orders.index') }}" 
           class="{{ request()->routeIs('online-orders.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center justify-between px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1.5">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('online-orders.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                Online Orders
            </div>
            @if($pendingWebOrders > 0)
                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">{{ $pendingWebOrders }}</span>
            @endif
        </a>
        @endif

        @if(Auth::user()->isAdminUser())
        <a href="{{ route('accounts.opening-balance') }}"
           class="{{ request()->routeIs('accounts.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1.5">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('accounts.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            Accounts
        </a>

        <a href="{{ route('analytics.overview') }}"
           class="{{ request()->routeIs('analytics.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1.5">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('analytics.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Reports
        </a>
        <a href="{{ route('reports.daily') }}"
           class="{{ request()->routeIs('reports.daily') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('reports.daily') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Cash / Card / bKash
        </a>
        <a href="{{ route('reports.staff_performance') }}"
           class="{{ request()->routeIs('reports.staff_performance', 'reports.staff_daily_details') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('reports.staff_performance') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Top Sellers
        </a>
        @endif

        <div class="pt-2 border-t border-white/5 mt-2">
            <a href="{{ route('customers.index') }}" 
               class="{{ request()->routeIs('customers.index', 'customers.create', 'customers.edit') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('customers.index', 'customers.create', 'customers.edit') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Customers
            </a>
            <a href="{{ route('customers.baki.index') }}"
               class="{{ request()->routeIs('customers.baki.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('customers.baki.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Customer Baki
            </a>
            <a href="{{ route('customers.emi.index') }}"
               class="{{ request()->routeIs('customers.emi.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm mt-1">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('customers.emi.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Customer EMI
            </a>
        </div>
        @endcan

        @if(Auth::user()->can('manage roles') || Auth::user()->can('manage staff') || Auth::user()->can('manage counters'))
            <div class="pt-2 border-t border-white/5 mt-2">
                
                @can('manage roles')
                <div class="mb-1.5">
                    <a href="{{ route('roles.index') }}" 
                       class="{{ request()->routeIs('roles.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('roles.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Roles
                    </a>
                </div>
                @endcan

                @can('manage staff')
                <div class="mb-1.5">
                    <a href="{{ route('staff.index') }}" 
                       class="{{ request()->routeIs('staff.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('staff.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Staff
                    </a>
                </div>
                @endcan

                @can('manage counters')
                <div class="mb-1.5">
                    <a href="{{ route('counters.index') }}" 
                       class="{{ request()->routeIs('counters.index') || request()->routeIs('counters.store') || request()->routeIs('counters.update') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('counters.index') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Counters
                    </a>
                </div>
                @endcan

            </div>
        @endif

        @if(Auth::user()->can('manage counters') || Auth::user()->can('process pos sales'))
        <div class="pt-2 border-t border-white/5 mt-2 mb-1.5">
            <a href="{{ route('counters.sessions.index') }}"
               class="{{ request()->routeIs('counters.sessions.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-white/5 hover:text-white' }} flex items-center px-3 py-2.5 rounded-lg transition-all font-medium text-sm">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('counters.sessions.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Cash Sessions
            </a>
        </div>
        @endif

    </nav>
</aside>

<header class="fixed top-0 right-0 left-0 lg:left-[260px] h-[4.25rem] bg-white/90 backdrop-blur border-b border-slate-100 z-30 flex items-center gap-3 px-4 sm:px-6">
    <button type="button" @click="sidebarOpen = true" class="lg:hidden text-slate-500 hover:text-slate-800 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    @include('components.command-palette')

    <div class="flex items-center gap-1.5 sm:gap-2 ml-auto shrink-0">
        @can('process pos sales')
            <a href="{{ route('pos.index') }}"
               onclick="return window.launchPosTerminal(this.href)"
               class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-2.5 sm:px-3.5 py-2 text-[11px] sm:text-[12px] font-bold text-white hover:bg-slate-800">
                <span class="sm:hidden">POS</span>
                <span class="hidden sm:inline">POS Terminal</span>
            </a>
        @endcan

        @if(Auth::check())
            <a href="{{ route('home') }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-2.5 sm:px-3.5 py-2 text-[11px] sm:text-[12px] font-bold text-white hover:bg-blue-700">
                <span class="sm:hidden">Store</span>
                <span class="hidden sm:inline">View Store</span>
            </a>
        @endif

        @can('view sales ledger')
            @if(Auth::user()->isAdminUser())
            <div class="relative"
                 x-data="onlineOrderBell(@js(route('online-orders.notifications')), @js(route('online-orders.notifications.seen')))"
                 @keydown.escape.window="if (panelOpen) closePanel()"
                 @click.outside="if (panelOpen) closePanel()">
                <button type="button"
                        @click="togglePanel($event)"
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:text-slate-800"
                        :class="panelOpen ? 'border-blue-300 bg-blue-50 text-blue-700' : ''"
                        title="Online order notifications"
                        aria-haspopup="true"
                        :aria-expanded="panelOpen">
                    <svg class="h-4 w-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
                          class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white"></span>
                </button>

                <div x-show="panelOpen"
                     x-cloak
                     x-transition.opacity.duration.150ms
                     class="absolute right-0 top-[calc(100%+8px)] z-50 admin-fluid-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                     style="display: none;">
                    <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-2.5">
                        <p class="text-[13px] font-bold text-slate-900">Online orders</p>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="loading ? 'Loading…' : (items.length + ' recent')"></span>
                    </div>

                    <div class="max-h-[360px] overflow-y-auto">
                        <template x-if="error && !loading">
                            <p class="px-4 py-6 text-center text-[12px] text-rose-500" x-text="error"></p>
                        </template>

                        <template x-if="!loading && !error && items.length === 0">
                            <p class="px-4 py-8 text-center text-[12px] text-slate-400">No online orders yet.</p>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url"
                               @click="openItem(item, $event)"
                               class="block border-b border-slate-50 px-3.5 py-2.5 transition last:border-0"
                               :class="item.is_new ? 'bg-indigo-50/80 hover:bg-indigo-50' : 'bg-white hover:bg-slate-50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-[12px] font-bold" :class="item.is_new ? 'text-indigo-900' : 'text-slate-700'" x-text="item.invoice"></p>
                                            <span x-show="item.is_new" class="rounded-full bg-indigo-600 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">New</span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px]" :class="item.is_new ? 'text-indigo-700' : 'text-slate-500'" x-text="item.customer + (item.phone ? ' · ' + item.phone : '')"></p>
                                        <p class="mt-0.5 text-[10px]" :class="item.is_new ? 'text-indigo-500' : 'text-slate-400'" x-text="item.status_label + ' · ' + item.at"></p>
                                    </div>
                                    <p class="shrink-0 text-[11px] font-bold" :class="item.is_new ? 'text-indigo-800' : 'text-slate-500'" x-text="'Tk ' + item.total"></p>
                                </div>
                            </a>
                        </template>
                    </div>

                    <a href="{{ route('online-orders.index') }}"
                       class="block border-t border-slate-100 bg-slate-50 px-3.5 py-2.5 text-center text-[12px] font-bold text-indigo-600 hover:bg-slate-100">
                        View all online orders
                    </a>
                </div>
            </div>
            @endif
        @endcan

        <div class="flex items-center pl-0.5 sm:pl-1">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden lg:inline max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-rose-600">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>