<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use App\Models\SiteFeature;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    use ShopScoped;

    public function edit()
    {
        $settings = SiteSetting::current();
        if (!$settings->exists) {
            $settings->default_shop_id = $this->shopId();
            $settings->save();
            $settings = SiteSetting::current();
        }

        $features = SiteFeature::where('shop_id', $this->shopId())->orderBy('sort_order')->orderBy('id')->get();
        $banners = PromoBanner::where('shop_id', $this->shopId())->orderBy('sort_order')->orderBy('id')->get();

        return view('cms.landing.edit', compact('settings', 'features', 'banners'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:255',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => [
                'required',
                'string',
                'max:10',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $v = trim((string) $value);
                    if ($v === '' || preg_match('/^\+?\d{2,4}$/', $v) || in_array($v, ['880', '+880', '088', '88'], true)) {
                        $fail('Currency symbol cannot be a phone country code like 880. Use ৳, Tk, or BDT.');
                    }
                },
            ],
            'special_offer_text' => 'nullable|string|max:255',
            'trusted_by_text' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:500',
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'favicon' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg,ico|max:2048',
            'features' => 'nullable|array',
            'features.*.id' => 'nullable|integer',
            'features.*.icon' => 'nullable|string|max:50',
            'features.*.title' => 'nullable|string|max:255',
            'features.*.subtitle' => 'nullable|string|max:255',
            'features.*.sort_order' => 'nullable|integer|min:0',
            'features.*.is_active' => 'nullable|boolean',
            'banners' => 'nullable|array',
            'banners.*.id' => 'nullable|integer',
            'banners.*.title' => 'nullable|string|max:255',
            'banners.*.subtitle' => 'nullable|string|max:255',
            'banners.*.price_from' => 'nullable|numeric|min:0',
            'banners.*.button_text' => 'nullable|string|max:100',
            'banners.*.button_url' => 'nullable|string|max:255',
            'banners.*.theme' => 'nullable|in:dark,light',
            'banners.*.sort_order' => 'nullable|integer|min:0',
            'banners.*.is_active' => 'nullable|boolean',
            'banners.*.image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $website = app(\App\Services\WebsiteService::class);
        $data['currency_symbol'] = $website->normalizeCurrencySymbol(
            $data['currency_symbol'],
            $data['currency_code']
        );

        $settings = SiteSetting::current();
        if (!$settings->exists) {
            $settings = new SiteSetting();
        }

        $settings->fill([
            'default_shop_id' => $this->shopId(),
            'store_name' => $data['store_name'],
            'currency_code' => $data['currency_code'],
            'currency_symbol' => $data['currency_symbol'],
            'special_offer_text' => $data['special_offer_text'] ?? null,
            'trusted_by_text' => $data['trusted_by_text'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_address' => $data['contact_address'] ?? null,
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = $request->file('logo')->store('cms/logo', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $settings->favicon_path = $request->file('favicon')->store('cms/favicon', 'public');
        }

        $settings->save();

        $this->syncFeatures($request->input('features', []));
        $this->syncBanners($request);

        return back()->with('success', 'Landing page settings saved. Changes appear on the public storefront.');
    }

    private function syncFeatures(array $rows): void
    {
        $keep = [];
        foreach ($rows as $row) {
            if (blank($row['title'] ?? null)) {
                continue;
            }
            $payload = [
                'shop_id' => $this->shopId(),
                'icon' => $row['icon'] ?: 'truck',
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']),
            ];
            if (!empty($row['id'])) {
                $feature = SiteFeature::where('shop_id', $this->shopId())->find($row['id']);
                if ($feature) {
                    $feature->update($payload);
                    $keep[] = $feature->id;
                    continue;
                }
            }
            $keep[] = SiteFeature::create($payload)->id;
        }

        SiteFeature::where('shop_id', $this->shopId())->whereNotIn('id', $keep ?: [0])->delete();
    }

    private function syncBanners(Request $request): void
    {
        $rows = $request->input('banners', []);
        $files = $request->file('banners', []);
        $keep = [];

        foreach ($rows as $i => $row) {
            if (blank($row['title'] ?? null)) {
                continue;
            }
            $payload = [
                'shop_id' => $this->shopId(),
                'title' => $row['title'],
                'subtitle' => $row['subtitle'] ?? null,
                'price_from' => $row['price_from'] ?? null,
                'button_text' => $row['button_text'] ?: 'Shop Now',
                'button_url' => $row['button_url'] ?? null,
                'theme' => $row['theme'] ?? 'dark',
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']),
            ];

            $banner = null;
            if (!empty($row['id'])) {
                $banner = PromoBanner::where('shop_id', $this->shopId())->find($row['id']);
            }

            if (!empty($files[$i]['image'])) {
                if ($banner?->image_path) {
                    Storage::disk('public')->delete($banner->image_path);
                }
                $payload['image_path'] = $files[$i]['image']->store('cms/banners', 'public');
            }

            if ($banner) {
                $banner->update($payload);
                $keep[] = $banner->id;
            } else {
                $keep[] = PromoBanner::create($payload)->id;
            }
        }

        PromoBanner::where('shop_id', $this->shopId())->whereNotIn('id', $keep ?: [0])->delete();
    }
}
