@props([
    'totalKey' => 'filteredCount',
])

<div {{ $attributes->merge(['class' => 'px-4 py-3 border-t border-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3']) }}>
    <p class="text-[11px] text-gray-500">
        Showing
        <span class="font-bold text-gray-700" x-text="rangeStart"></span>
        to
        <span class="font-bold text-gray-700" x-text="rangeEnd"></span>
        of
        <span class="font-bold text-gray-700" x-text="{{ $totalKey }}"></span>
    </p>
    <div class="flex items-center gap-1.5" x-show="totalPages > 1">
        <button type="button" @click="goToPage(1)" :disabled="page === 1"
                class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                title="First page">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                title="Previous">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <template x-for="p in totalPages" :key="'pg-' + p">
            <button type="button" @click="goToPage(p)"
                    class="min-w-[2rem] h-8 px-2 rounded-lg text-xs font-bold transition"
                    :class="page === p ? 'bg-indigo-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    x-text="p"
                    x-show="totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - page) <= 1"></button>
        </template>
        <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages"
                class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                title="Next">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" @click="goToPage(totalPages)" :disabled="page === totalPages"
                class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                title="Last page">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>
