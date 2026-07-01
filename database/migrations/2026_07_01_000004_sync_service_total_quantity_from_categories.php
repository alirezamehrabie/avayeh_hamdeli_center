<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasTable('service_categories')) {
            return;
        }

        DB::table('services')->update([
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
        // The previous service total cannot be reconstructed once category totals become canonical.
    }
};
