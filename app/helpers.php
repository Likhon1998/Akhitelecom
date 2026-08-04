<?php

if (! function_exists('public_storage_url')) {
    /**
     * Public disk files live under storage/app/public and are served from {base}/storage/...
     * Uses the current request base path so subdirectory installs (XAMPP) work.
     */
    function public_storage_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        try {
            $base = rtrim(request()->getBasePath(), '/');

            // Prefer path-only URLs so the browser always loads from the current host/port.
            return $base.'/storage/'.$path;
        } catch (\Throwable) {
            return '/storage/'.$path;
        }
    }
}

if (! function_exists('site_favicon_url')) {
    /**
     * Browser tab icon: custom favicon → store logo → branded default SVG.
     */
    function site_favicon_url(?object $settings = null): string
    {
        if ($settings === null) {
            try {
                $settings = \App\Models\SiteSetting::query()->first();
            } catch (\Throwable) {
                $settings = null;
            }
        }

        $favicon = data_get($settings, 'favicon_path');
        if (filled($favicon)) {
            return public_storage_url($favicon);
        }

        $logo = data_get($settings, 'logo_path');
        if (filled($logo)) {
            return public_storage_url($logo);
        }

        return asset('favicon.svg');
    }
}

if (! function_exists('app_timezone')) {
    function app_timezone(): string
    {
        return config('app.display_timezone', config('app.timezone', 'Asia/Dhaka'));
    }
}

if (! function_exists('asian_datetime')) {
    /**
     * Format a date/time in Asia/Dhaka (or APP_DISPLAY_TIMEZONE).
     */
    function asian_datetime($value = null, string $format = 'd M Y, h:i A'): string
    {
        if ($value === null || $value === '') {
            $value = now();
        }

        try {
            $dt = $value instanceof \Carbon\CarbonInterface
                ? $value->copy()
                : \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return '';
        }

        return $dt->timezone(app_timezone())->format($format);
    }
}

if (! function_exists('asian_date')) {
    function asian_date($value = null, string $format = 'd M Y'): string
    {
        return asian_datetime($value, $format);
    }
}

if (! function_exists('normalize_memory_size')) {
    /**
     * Normalize RAM / storage labels: "8GB", "8gb", "8 GB" → "8 GB".
     */
    function normalize_memory_size(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(tb|gb|mb|kb)$/i', $trimmed, $m)) {
            $number = $m[1];
            if (str_contains($number, '.')) {
                $number = rtrim(rtrim($number, '0'), '.');
            }

            return $number.' '.strtoupper($m[2]);
        }

        return $trimmed;
    }
}

if (! function_exists('memory_size_compact')) {
    /** Compact form for equality checks: "8 GB" → "8gb". */
    function memory_size_compact(?string $value): string
    {
        $normalized = normalize_memory_size($value);

        return $normalized ? strtolower(str_replace(' ', '', $normalized)) : '';
    }
}

if (! function_exists('memory_size_sort_key')) {
    /** Sort key in megabytes for numeric ordering. */
    function memory_size_sort_key(?string $value): float
    {
        $normalized = normalize_memory_size($value);
        if (! $normalized || ! preg_match('/^(\d+(?:\.\d+)?)\s*(TB|GB|MB|KB)$/', $normalized, $m)) {
            return PHP_FLOAT_MAX;
        }

        $n = (float) $m[1];

        return match ($m[2]) {
            'TB' => $n * 1024 * 1024,
            'GB' => $n * 1024,
            'MB' => $n,
            'KB' => $n / 1024,
            default => PHP_FLOAT_MAX,
        };
    }
}

if (! function_exists('unique_memory_sizes')) {
    /**
     * Deduplicate and sort memory labels (RAM / ROM).
     *
     * @param  iterable<int, mixed>  $values
     * @return \Illuminate\Support\Collection<int, string>
     */
    function unique_memory_sizes(iterable $values): \Illuminate\Support\Collection
    {
        return collect($values)
            ->map(fn ($v) => normalize_memory_size(is_string($v) ? $v : (string) $v))
            ->filter()
            ->unique(fn ($v) => memory_size_compact($v))
            ->sortBy(fn ($v) => memory_size_sort_key($v))
            ->values();
    }
}

