<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production hotfix: SoftDeletes is on Product, but deleted_at was missing
 * (site 500 / “destroyed” layout). Safe to re-run — no-ops if column exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'deleted_at')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Keep column — removing it would break SoftDeletes again.
    }
};
