<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'default_shop_id', 'store_name', 'logo_path', 'favicon_path',
        'currency_code', 'currency_symbol', 'special_offer_text',
        'blog_hero_kicker', 'blog_hero_title', 'blog_hero_subtitle', 'blog_hero_image',
        'blog_newsletter_title', 'blog_newsletter_text',
        'blog_articles_title',
        'blog_feature_1_title', 'blog_feature_1_text',
        'blog_feature_2_title', 'blog_feature_2_text',
        'blog_feature_3_title', 'blog_feature_3_text',
        'faq_hero_title', 'faq_hero_subtitle',
        'faq_help_title', 'faq_help_text', 'faq_help_button',
        'contact_hero_kicker', 'contact_hero_title', 'contact_hero_subtitle',
        'contact_chat_title', 'contact_chat_text', 'contact_chat_status',
        'contact_email_card_title', 'contact_email_card_text',
        'contact_phone_card_title', 'contact_phone_card_text',
        'contact_hours_title', 'contact_hours_weekday', 'contact_hours_weekend',
        'contact_form_title', 'contact_form_subtitle',
        'contact_map_embed', 'contact_website_url',
        'contact_newsletter_title', 'contact_newsletter_text',
        'trusted_by_text',
        'deals_kicker', 'deals_title', 'deals_title_accent', 'deals_subtitle',
        'contact_email', 'contact_phone',
        'contact_address', 'social_links',
        'delivery_inside_dhaka', 'delivery_outside_dhaka',
        'delivery_free_enabled', 'delivery_free_min_amount',
        'delivery_cod_enabled', 'delivery_confirmation_enabled',
        'delivery_confirmation_amount',
    ];

    protected $casts = [
        'social_links' => 'array',
        'delivery_inside_dhaka' => 'decimal:2',
        'delivery_outside_dhaka' => 'decimal:2',
        'delivery_free_enabled' => 'boolean',
        'delivery_free_min_amount' => 'decimal:2',
        'delivery_cod_enabled' => 'boolean',
        'delivery_confirmation_enabled' => 'boolean',
        'delivery_confirmation_amount' => 'decimal:2',
    ];

    public function defaultShop()
    {
        return $this->belongsTo(Shop::class, 'default_shop_id');
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'store_name' => config('app.name', 'Akhi Telecom'),
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);
    }

    /**
     * Path of the brand image shown on the public homepage (CMS Landing).
     * Prefers Full logo, then favicon. Prefers a file that exists on disk.
     */
    public function storeBrandImagePath(): ?string
    {
        $candidates = [];
        foreach ([$this->logo_path, $this->favicon_path] as $path) {
            if (filled($path)) {
                $candidates[] = (string) $path;
            }
        }

        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $path) {
            if (preg_match('#^(https?:)?//#i', $path)) {
                return $path;
            }
            if ($this->brandImageAbsolutePath($path)) {
                return $path;
            }
        }

        return $candidates[0];
    }

    /**
     * Browser URL for the homepage brand image (path-only, same as storefront).
     * Avoids absolute APP_URL links that break when APP_URL differs from the request host.
     * Falls back to the bundled AT brand mark in public/images/brand.
     */
    public function storeLogoUrl(): ?string
    {
        $path = $this->storeBrandImagePath();
        if ($path) {
            if (preg_match('#^(https?:)?//#i', $path)) {
                return $path;
            }

            $url = public_storage_url($path);
            if ($url) {
                $mtime = @filemtime($this->brandImageAbsolutePath($path) ?? '') ?: time();

                return $url.(str_contains($url, '?') ? '&' : '?').'v='.$mtime;
            }
        }

        return function_exists('default_brand_logo_url') ? default_brand_logo_url() : null;
    }

    /**
     * Data-URI of the homepage brand image for print (works offline / thermal).
     * Falls back to bundled AT brand mark, then a same-origin URL.
     */
    public function storeLogoSrc(): ?string
    {
        $path = $this->storeBrandImagePath();
        if ($path) {
            if (preg_match('#^(https?:)?//#i', $path)) {
                return $path;
            }

            $absolute = $this->brandImageAbsolutePath($path);
            if ($absolute) {
                $bytes = @file_get_contents($absolute);
                if ($bytes !== false && $bytes !== '') {
                    return 'data:'.$this->brandImageMime($absolute, $path).';base64,'.base64_encode($bytes);
                }
            }
        }

        $bundled = function_exists('default_brand_logo_absolute') ? default_brand_logo_absolute() : null;
        if ($bundled) {
            $bytes = @file_get_contents($bundled);
            if ($bytes !== false && $bytes !== '') {
                return 'data:image/png;base64,'.base64_encode($bytes);
            }
        }

        return $this->storeLogoUrl();
    }

    /**
     * Resolve a public-disk relative path to a readable absolute file.
     * Tries Storage disk root, public/storage (web symlink), and storage/app/public.
     */
    private function brandImageAbsolutePath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $candidates = array_unique(array_filter([
            public_storage_path($path),
            public_path('storage/'.$path),
            storage_path('app/public/'.$path),
        ]));

        foreach ($candidates as $absolute) {
            if (is_string($absolute) && is_file($absolute) && is_readable($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    private function brandImageMime(string $absolute, string $path): string
    {
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($absolute);
            if (is_string($detected) && str_starts_with($detected, 'image/')) {
                return $detected;
            }
        }

        if (preg_match('/\.(jpe?g)$/i', $path)) {
            return 'image/jpeg';
        }
        if (preg_match('/\.gif$/i', $path)) {
            return 'image/gif';
        }
        if (preg_match('/\.webp$/i', $path)) {
            return 'image/webp';
        }
        if (preg_match('/\.svg$/i', $path)) {
            return 'image/svg+xml';
        }

        return 'image/png';
    }
}
