<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'value_per_unit')) {
            return;
        }

        $this->dropColumnSafely('services', 'value_per_unit');
    }

    public function down(): void
    {
        if (! Schema::hasTable('services') || Schema::hasColumn('services', 'value_per_unit')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            $table->unsignedBigInteger('value_per_unit')->default(0)->after('total_quantity');
        });

        DB::table('services')
            ->orderBy('id')
            ->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $totalQuantity = (float) ($service->total_quantity ?? 0);
                    $totalValue = (float) ($service->total_service_value ?? 0);
                    $valuePerUnit = $totalQuantity > 0 ? (int) round($totalValue / $totalQuantity) : 0;

                    DB::table('services')
                        ->where('id', $service->id)
                        ->update([
                            'value_per_unit' => $valuePerUnit,
                        ]);
                }
            });
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
