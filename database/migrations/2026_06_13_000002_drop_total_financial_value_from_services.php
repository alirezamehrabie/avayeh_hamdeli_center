<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'total_financial_value')) {
            return;
        }

        $this->dropColumnSafely('services', 'total_financial_value');
    }

    public function down(): void
    {
        if (! Schema::hasTable('services') || Schema::hasColumn('services', 'total_financial_value')) {
            return;
        }

        Schema::table('services', function ($table): void {
            $table->unsignedBigInteger('total_financial_value')->default(0)->after('total_service_value');
        });

        DB::table('services')->update([
            'total_financial_value' => DB::raw('total_service_value'),
        ]);
    }

    protected function dropColumnSafely(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`');
        } catch (\Throwable) {
            // Ignore already-dropped columns.
        }
    }
};
