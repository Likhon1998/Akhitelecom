<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_imeis', 'location_id')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_imeis', 'location_id')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
