@props(['title', 'subtitle' => null, 'actionUrl' => null, 'actionLabel' => '+ Add New', 'previewUrl' => null])

<x-app-layout>
    <div class="pb-5 sm:pb-6 min-w-0">
        <div class="mb-3 sm:mb-4 mt-0.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 mb-0.5">CMS · Website</p>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-950 tracking-tight">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-sm text-gray-500 font-medium">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($previewUrl)
                    <a href="{{ $previewUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        View on website
                    </a>
                @endif
                @if($actionUrl)
                    <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-3.5 sm:px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">
                        {{ $actionLabel }}
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div data-admin-flash-banner class="mb-3 p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-lg font-semibold text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div data-admin-flash-banner class="mb-3 p-3 bg-red-50 border border-red-100 text-red-800 rounded-lg font-semibold text-sm">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 p-3 bg-red-50 border border-red-100 text-red-800 rounded-lg text-sm">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
