<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_units')) {
            return;
        }

        $units = [
            ['key' => 'rial', 'label' => 'ریال', 'sort_id' => 7],
            ['key' => 'pair', 'label' => 'جفت', 'sort_id' => 8],
        ];

        foreach ($units as $unit) {
            $existingUnit = DB::table('service_units')
                ->where('key', $unit['key'])
                ->first();

            if ($existingUnit) {
                DB::table('service_units')
                    ->where('id', $existingUnit->id)
                    ->update([
                        'label' => $unit['label'],
                        'sort_id' => $unit['sort_id'],
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('service_units')->insert([
                'key' => $unit['key'],
                'label' => $unit['label'],
                'sort_id' => $unit['sort_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_units')) {
            return;
        }

        DB::table('service_units')
            ->whereIn('key', ['rial', 'pair'])
            ->delete();
    }
};
