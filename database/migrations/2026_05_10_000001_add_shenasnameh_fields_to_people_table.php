<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->char('shenasnameh_serial', 6)->nullable()->after('national_id');
            $table->char('shenasnameh_series_number', 2)->nullable()->after('shenasnameh_serial');
            $table->char('shenasnameh_series_letter', 1)->nullable()->after('shenasnameh_series_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'shenasnameh_serial',
                'shenasnameh_series_number',
                'shenasnameh_series_letter',
            ]);
        });
    }
};
