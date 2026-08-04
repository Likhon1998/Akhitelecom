<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\HeroSlide;
use App\Models\NavigationLink;
use App\Models\PromoBanner;
use App\Models\Shop;
use App\Models\SiteFeature;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::first();
        if (!$shop) {
            return;
        }

        SiteSetting::query()->delete();
        NavigationLink::where('shop_id', $shop->id)->delete();
        HeroSlide::where('shop_id', $shop->id)->delete();
        SiteFeature::where('shop_id', $shop->id)->delete();
        PromoBanner::where('shop_id', $shop->id)->delete();
        Brand::where('shop_id', $shop->id)->delete();

        SiteSetting::create([
            'default_shop_id' => $shop->id,
            'store_name' => 'GAGET STORE',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'special_offer_text' => 'Special Offer!',
            'trusted_by_text' => 'Trusted by customers across Bangladesh',
            'deals_kicker' => 'SPECIAL OFFERS',
            'deals_title' => "Deals You'll",
            'deals_title_accent' => 'Love',
            'deals_subtitle' => 'Grab the best deals on top-quality gadgets and accessories.',
            'contact_email' => 'hello@gagetstore.com',
            'contact_phone' => '+880 1700-000000',
            'contact_address' => 'Dhaka, Bangladesh',
        ]);

        // Homepage hero posters (5 banners with images).
        $this->call(HeroSlideSeeder::class);

        // Homepage trust features (4 items — editable in CMS → Landing Page).
        $this->call(SiteFeatureSeeder::class);

        $promos = [
            [
                'title' => 'SOMOSTEL B2',
                'subtitle' => 'Up to 40% Off',
                'badge_text' => 'BEST SELLER',
                'highlight_text' => '40% Off',
                'discount_badge' => '-40%',
                'price_from' => 749.98,
                'theme' => 'dark',
                'sort_order' => 1,
            ],
            [
                'title' => 'SOMOSTEL B3',
                'subtitle' => 'Supercharged by M3',
                'badge_text' => 'MEGA POWER',
                'highlight_text' => null,
                'discount_badge' => null,
                'price_from' => 749.00,
                'theme' => 'light',
                'sort_order' => 2,
            ],
        ];
        foreach ($promos as $p) {
            PromoBanner::create(array_merge($p, ['shop_id' => $shop->id, 'button_text' => 'Shop Now', 'button_url' => '/shop', 'is_active' => true]));
        }

        foreach (['Apple', 'Samsung', 'Sony', 'Bose', 'Canon', 'Dell', 'Xiaomi'] as $i => $name) {
            Brand::create(['shop_id' => $shop->id, 'name' => $name, 'sort_order' => $i + 1, 'is_active' => true]);
        }

        $navLinks = [
            ['label' => 'Home', 'url' => '/', 'location' => 'main_nav', 'sort_order' => 1],
            ['label' => 'Shop', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 2],
            ['label' => 'Categories', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 3],
            ['label' => 'Deals', 'url' => '/shop?filter=deals', 'location' => 'main_nav', 'sort_order' => 4],
            ['label' => 'New Arrivals', 'url' => '/shop?filter=new', 'location' => 'main_nav', 'sort_order' => 5],
            ['label' => 'Brands', 'url' => '/#brands', 'location' => 'main_nav', 'sort_order' => 6],
            ['label' => 'Blog', 'url' => '/blog', 'location' => 'main_nav', 'sort_order' => 7],
            ['label' => 'Contact', 'url' => '/contact', 'location' => 'main_nav', 'sort_order' => 8],
            ['label' => 'Cash on delivery available', 'url' => '/shop', 'location' => 'top_bar', 'sort_order' => 1],
            ['label' => '30-day easy returns', 'url' => '#', 'location' => 'top_bar', 'sort_order' => 2],
            ['label' => '1 Year Warranty', 'url' => '#', 'location' => 'top_bar', 'sort_order' => 3],
        ];
        foreach ($navLinks as $link) {
            NavigationLink::create(array_merge($link, ['shop_id' => $shop->id, 'is_active' => true]));
        }
    }
}
