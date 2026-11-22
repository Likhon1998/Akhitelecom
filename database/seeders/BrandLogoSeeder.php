<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BrandLogoSeeder extends Seeder
{
    /**
     * Install / refresh the bundled AT brand mark as CMS Full logo + Favicon.
     * Safe to re-run. Admins can replace either file anytime in CMS → Landing.
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

        foreach ([$logoRel, $faviconRel] as $rel) {
            $dest = public_storage_path($rel);
            File::ensureDirectoryExists(dirname($dest));
            File::copy($source, $dest);
        }

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

        $this->command?->info('CMS logo + favicon set to AT brand mark (change anytime in CMS → Landing).');
    }
}
