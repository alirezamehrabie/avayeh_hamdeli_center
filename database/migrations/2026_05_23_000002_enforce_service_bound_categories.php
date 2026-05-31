<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_categories')->whereNull('service_name_id')->delete();

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropUnique(['service_name_id', 'name']);
        });

        DB::statement('ALTER TABLE service_categories MODIFY service_name_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE service_categories MODIFY sort_id INT UNSIGNED NOT NULL');

        Schema::table('service_categories', function (Blueprint $table) {
            $table->unique(['service_name_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropUnique(['service_name_id', 'name']);
        });

        DB::statement('ALTER TABLE service_categories MODIFY service_name_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE service_categories MODIFY sort_id INT UNSIGNED NULL');

        Schema::table('service_categories', function (Blueprint $table) {
            $table->unique(['service_name_id', 'name']);
        });
    }
};
