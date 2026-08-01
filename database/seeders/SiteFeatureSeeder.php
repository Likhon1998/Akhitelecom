<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

class SiteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $features = [
            [
                'icon' => 'truck',
                'title' => 'Free Shipping',
                'subtitle' => 'On all orders over $50',
                'sort_order' => 1,
            ],
            [
                'icon' => 'return',
                'title' => '30-Day Returns',
                'subtitle' => 'Hassle-free returns',
                'sort_order' => 2,
            ],
            [
                'icon' => 'lock',
                'title' => 'Secure Payments',
                'subtitle' => '100% secure checkout',
                'sort_order' => 3,
            ],
            [
                'icon' => 'shield',
                'title' => '1 Year Warranty',
                'subtitle' => 'Product warranty included',
                'sort_order' => 4,
            ],
        ];

        $keepTitles = [];

        foreach ($features as $feature) {
            $keepTitles[] = $feature['title'];

            SiteFeature::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'title' => $feature['title'],
                ],
                [
                    'icon' => $feature['icon'],
                    'subtitle' => $feature['subtitle'],
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Keep exactly these 4 homepage features active by default.
        SiteFeature::where('shop_id', $shop->id)
            ->whereNotIn('title', $keepTitles)
            ->update(['is_active' => false]);
    }
}
