<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_categories') || ! Schema::hasTable('service_deliveries')) {
            return;
        }

        DB::table('service_categories')
            ->whereNull('deleted_at')
            ->update([
                'quantity' => DB::raw(
                    'quantity + COALESCE(('
                    . 'SELECT SUM(service_deliveries.delivered_quantity) '
                    . 'FROM service_deliveries '
                    . 'WHERE service_deliveries.service_category_id = service_categories.id '
                    . 'AND service_deliveries.deleted_at IS NULL'
                    . '), 0)'
                ),
            ]);

        DB::table('services')
            ->update([
                'total_quantity' => DB::raw(
                    'COALESCE(('
                    . 'SELECT SUM(service_categories.quantity) '
                    . 'FROM service_categories '
                    . 'WHERE service_categories.service_id = services.id '
                    . 'AND service_categories.deleted_at IS NULL'
                    . '), 0)'
                ),
            ]);
    }

    public function down(): void
    {
        // Service category quantities are baseline definitions; do not reapply historic delivery deductions.
    }
};
