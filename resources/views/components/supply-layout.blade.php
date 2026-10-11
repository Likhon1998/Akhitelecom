@props([
    'title',
    'subtitle' => null,
    'actionUrl' => null,
    'actionLabel' => '+ Add New',
])
<x-app-layout>
    <div class="pb-5 sm:pb-6 min-w-0">
        <div class="mb-3 sm:mb-4 mt-0.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-950 tracking-tight">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-sm text-gray-500 font-medium">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actionUrl)
                <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center gap-2 self-start bg-indigo-600 text-white px-3.5 sm:px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all shrink-0">
                    {{ $actionLabel }}
                </a>
            @endif
        </div>
        @include('supply.partials.alerts')
        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
