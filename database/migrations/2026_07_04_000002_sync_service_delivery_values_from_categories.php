<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_deliveries') || ! Schema::hasTable('service_categories')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                UPDATE service_deliveries sd
                INNER JOIN service_categories sc ON sc.id = sd.service_category_id
                SET
                    sd.value_per_unit_snapshot = COALESCE(sc.value, 0),
                    sd.delivered_total_value = ROUND(sd.delivered_quantity * COALESCE(sc.value, 0))
                WHERE sd.service_category_id IS NOT NULL
            SQL);

            return;
        }

        DB::table('service_deliveries')
            ->whereNotNull('service_category_id')
            ->orderBy('id')
            ->chunkById(500, function ($deliveries): void {
                $categoryValues = DB::table('service_categories')
                    ->whereIn('id', $deliveries->pluck('service_category_id')->filter()->unique()->all())
                    ->pluck('value', 'id');

                foreach ($deliveries as $delivery) {
                    $valuePerUnit = (int) ($categoryValues[$delivery->service_category_id] ?? 0);

                    DB::table('service_deliveries')
                        ->where('id', $delivery->id)
                        ->update([
                            'value_per_unit_snapshot' => $valuePerUnit,
                            'delivered_total_value' => (int) round((float) $delivery->delivered_quantity * $valuePerUnit),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
