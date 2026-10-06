<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_imeis', 'imei_2')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table) {
            $table->string('imei_2', 32)->nullable()->unique()->after('imei');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_imeis', 'imei_2')) {
            return;
        }

        Schema::table('product_imeis', function (Blueprint $table) {
            $table->dropUnique(['imei_2']);
            $table->dropColumn('imei_2');
        });
    }
};
