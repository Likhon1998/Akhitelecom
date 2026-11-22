<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Install the bundled AT brand mark as the CMS Full logo + Favicon.
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

        foreach ([$logoRel, $faviconRel] as $rel) {
            $dest = function_exists('public_storage_path')
                ? public_storage_path($rel)
                : storage_path('app/public/'.$rel);
            File::ensureDirectoryExists(dirname($dest));
            File::copy($source, $dest);
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
