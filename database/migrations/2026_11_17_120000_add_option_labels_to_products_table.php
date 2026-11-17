<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'option1_label')) {
                $table->string('option1_label', 40)->nullable()->after('ram');
            }
            if (! Schema::hasColumn('products', 'option2_label')) {
                $table->string('option2_label', 40)->nullable()->after('option1_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'option2_label')) {
                $table->dropColumn('option2_label');
            }
            if (Schema::hasColumn('products', 'option1_label')) {
                $table->dropColumn('option1_label');
            }
        });
    }
};
