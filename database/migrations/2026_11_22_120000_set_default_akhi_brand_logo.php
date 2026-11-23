<?php

use App\Services\SiteLogoNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Install a transparent cropped AT brand mark as CMS Full logo + Favicon.
     * Admins can replace either anytime from CMS → Landing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $source = public_path('images/brand/akhi-telecom-at.png');
        if (! is_file($source)) {
            return;
        }

        $logoRel = 'cms/logo/akhi-telecom-at.png';
        $faviconRel = 'cms/favicon/akhi-telecom-at.png';
        $logoDest = function_exists('public_storage_path')
            ? public_storage_path($logoRel)
            : storage_path('app/public/'.$logoRel);
        $faviconDest = function_exists('public_storage_path')
            ? public_storage_path($faviconRel)
            : storage_path('app/public/'.$faviconRel);

        File::ensureDirectoryExists(dirname($logoDest));
        File::ensureDirectoryExists(dirname($faviconDest));

        try {
            $normalizer = app(SiteLogoNormalizer::class);
            $normalizer->normalizeToPng($source, $logoDest, square: false, padding: 6, threshold: 48, maxSize: 1600);
            $normalizer->normalizeToPng($source, $faviconDest, square: false, padding: 4, threshold: 48, maxSize: 512);
            // Refresh bundled public asset to transparent crop as well.
            $normalizer->normalizeToPng($source, $source, square: false, padding: 6, threshold: 48, maxSize: 1600);
        } catch (\Throwable) {
            File::copy($source, $logoDest);
            File::copy($source, $faviconDest);
        }

        $row = DB::table('site_settings')->orderBy('id')->first();
        if (! $row) {
            DB::table('site_settings')->insert([
                'store_name' => 'Akhi Telecom',
                'logo_path' => $logoRel,
                'favicon_path' => $faviconRel,
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('site_settings')->where('id', $row->id)->update([
            'logo_path' => $logoRel,
            'favicon_path' => $faviconRel,
            'store_name' => filled($row->store_name ?? null) ? $row->store_name : 'Akhi Telecom',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep installed brand files; do not wipe custom CMS settings on rollback.
    }
};
