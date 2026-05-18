<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MONEY_SCALE = 100;

    public function up(): void
    {
        DB::table('residences')
            ->select(['id', 'deposit_amount', 'monthly_rent'])
            ->orderBy('id')
            ->chunkById(200, function ($residences): void {
                foreach ($residences as $residence) {
                    DB::table('residences')
                        ->where('id', $residence->id)
                        ->update([
                            'deposit_amount' => $residence->deposit_amount === null
                                ? null
                                : intdiv((int) $residence->deposit_amount, self::MONEY_SCALE),
                            'monthly_rent' => $residence->monthly_rent === null
                                ? null
                                : intdiv((int) $residence->monthly_rent, self::MONEY_SCALE),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('residences')
            ->select(['id', 'deposit_amount', 'monthly_rent'])
            ->orderBy('id')
            ->chunkById(200, function ($residences): void {
                foreach ($residences as $residence) {
                    DB::table('residences')
                        ->where('id', $residence->id)
                        ->update([
                            'deposit_amount' => $residence->deposit_amount === null
                                ? null
                                : ((int) $residence->deposit_amount * self::MONEY_SCALE),
                            'monthly_rent' => $residence->monthly_rent === null
                                ? null
                                : ((int) $residence->monthly_rent * self::MONEY_SCALE),
                        ]);
                }
            });
    }
};
