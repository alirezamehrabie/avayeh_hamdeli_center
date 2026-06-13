<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->date('distribution_end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('services')
            ->whereNull('distribution_end_date')
            ->update([
                'distribution_end_date' => DB::raw('distribution_start_date'),
            ]);

        Schema::table('services', function (Blueprint $table): void {
            $table->date('distribution_end_date')->nullable(false)->change();
        });
    }
};
