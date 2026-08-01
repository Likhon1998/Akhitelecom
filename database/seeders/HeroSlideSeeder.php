<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $banners = [
            [
                'title' => 'The Future of Home Is Now',
                'badge_text' => 'SMART HOME',
                'description' => 'Smarter Living. Seamlessly Connected.',
                'price_from' => null,
                'button_text' => 'Explore the Future',
                'button_url' => '/shop',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 1,
                'file' => 'banner-smarthome.jpg',
            ],
            [
                'title' => 'iPhone 16 Pro Max',
                'badge_text' => 'NEW ARRIVAL',
                'description' => 'Titanium design. Pro camera. All-day battery.',
                'price_from' => 1299,
                'button_text' => 'Shop Now',
                'button_url' => '/shop?search=iphone',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=new',
                'sort_order' => 2,
                'file' => 'banner-iphone.jpg',
            ],
            [
                'title' => 'MacBook Air M3',
                'badge_text' => 'BEST SELLER',
                'description' => 'Impressively thin. Supercharged by Apple M3.',
                'price_from' => 1099,
                'button_text' => 'Shop Laptops',
                'button_url' => '/shop?search=macbook',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 3,
                'file' => 'banner-macbook.jpg',
            ],
            [
                'title' => 'Galaxy Watch Ultra',
                'badge_text' => 'HOT DEAL',
                'description' => 'Adventure-ready tracking with premium battery.',
                'price_from' => 399,
                'button_text' => 'Shop Watches',
                'button_url' => '/shop?search=watch',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=deals',
                'sort_order' => 4,
                'file' => 'banner-watch.jpg',
            ],
            [
                'title' => 'Premium Audio Sale',
                'badge_text' => 'UP TO 40% OFF',
                'description' => 'Headphones and earbuds from top brands.',
                'price_from' => 79,
                'button_text' => 'Shop Deals',
                'button_url' => '/shop?filter=deals',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 5,
                'file' => 'banner-audio.jpg',
            ],
        ];

        $keepTitles = [];

        foreach ($banners as $banner) {
            $keepTitles[] = $banner['title'];
            $imagePath = $this->storeSeedImage($banner['file']);

            HeroSlide::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'title' => $banner['title'],
                ],
                [
                    'badge_text' => $banner['badge_text'],
                    'description' => $banner['description'],
                    'price_from' => $banner['price_from'],
                    'image_path' => $imagePath,
                    'button_text' => $banner['button_text'],
                    'button_url' => $banner['button_url'],
                    'learn_more_text' => $banner['learn_more_text'],
                    'learn_more_url' => $banner['learn_more_url'],
                    'sort_order' => $banner['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Keep carousel at these 5 designed posters.
        HeroSlide::where('shop_id', $shop->id)
            ->whereNotIn('title', $keepTitles)
            ->delete();
    }

    /** Copy exact 1920×640 (3:1) poster art into public storage. */
    private function storeSeedImage(string $filename): ?string
    {
        $relative = 'cms/slides/'.$filename;
        $source = database_path('seeders/assets/slides/'.$filename);

        if (! File::exists($source)) {
            $this->command?->warn("Missing banner asset: {$source}");

            return Storage::disk('public')->exists($relative) ? $relative : null;
        }

        Storage::disk('public')->put($relative, File::get($source));

        return $relative;
    }
}
