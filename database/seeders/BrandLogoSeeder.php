<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Services\SiteLogoNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BrandLogoSeeder extends Seeder
{
    /**
     * Install a transparent, cropped AT brand mark as CMS Full logo + Favicon.
     * Admins can replace either file anytime in CMS → Landing.
     */
    public function run(): void
    {
        $source = public_path('images/brand/akhi-telecom-at.png');
        if (! is_file($source)) {
            $this->command?->warn('Bundled brand logo missing: public/images/brand/akhi-telecom-at.png');

            return;
        }

        $logoRel = 'cms/logo/akhi-telecom-at.png';
        $faviconRel = 'cms/favicon/akhi-telecom-at.png';
        $normalizer = app(SiteLogoNormalizer::class);

        $logoDest = public_storage_path($logoRel);
        $faviconDest = public_storage_path($faviconRel);
        File::ensureDirectoryExists(dirname($logoDest));
        File::ensureDirectoryExists(dirname($faviconDest));

        $normalizer->normalizeToPng($source, $logoDest, square: false, padding: 6, threshold: 48, maxSize: 1600);
        $normalizer->normalizeToPng($source, $faviconDest, square: false, padding: 4, threshold: 48, maxSize: 512);

        // Keep the public bundled copy transparent too (for asset fallbacks).
        $normalizer->normalizeToPng($source, $source, square: false, padding: 6, threshold: 48, maxSize: 1600);

        $settings = SiteSetting::query()->first();
        if (! $settings) {
            $settings = new SiteSetting([
                'store_name' => 'Akhi Telecom',
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
            ]);
        }

        $settings->logo_path = $logoRel;
        $settings->favicon_path = $faviconRel;
        if (! filled($settings->store_name)) {
            $settings->store_name = 'Akhi Telecom';
        }
        $settings->save();

        $this->command?->info('Transparent AT logo installed (CMS logo + favicon). Change anytime in CMS → Landing.');
    }
}
