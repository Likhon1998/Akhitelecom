{{-- Dynamic browser tab icon: favicon upload → store logo → branded default --}}
@php
    $faviconUrl = site_favicon_url($settings ?? null);
    $faviconType = str_ends_with(strtolower(parse_url($faviconUrl, PHP_URL_PATH) ?? ''), '.svg')
        ? 'image/svg+xml'
        : null;
@endphp
<link rel="icon" href="{{ $faviconUrl }}"@if($faviconType) type="{{ $faviconType }}"@endif>
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
