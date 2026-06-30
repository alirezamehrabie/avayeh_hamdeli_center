<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'total_service_value')) {
            return;
        }

        DB::statement('ALTER TABLE `services` MODIFY `total_service_value` BIGINT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'total_service_value')) {
            return;
        }

        DB::statement('ALTER TABLE `services` MODIFY `total_service_value` INT UNSIGNED NOT NULL DEFAULT 0');
    }
};
