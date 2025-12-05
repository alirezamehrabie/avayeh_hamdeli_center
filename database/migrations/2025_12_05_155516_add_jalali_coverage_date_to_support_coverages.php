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
        Schema::table('support_coverages', function (Blueprint $table) {
            // حذف فیلد قدیمی تاریخ (اگر وجود دارد)
            if (Schema::hasColumn('support_coverages', 'coverage_start_date')) {
                $table->dropColumn('coverage_start_date');
            }

            // افزودن فیلدهای جدید شمسی
            $table->tinyInteger('coverage_start_day')->nullable()->after('organization_name');
            $table->tinyInteger('coverage_start_month')->nullable()->after('coverage_start_day');
            $table->smallInteger('coverage_start_year')->nullable()->after('coverage_start_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_coverages', function (Blueprint $table) {
            // حذف فیلدهای شمسی
            $table->dropColumn(['coverage_start_day', 'coverage_start_month', 'coverage_start_year']);

            // بازگرداندن فیلد قدیمی
            $table->date('coverage_start_date')->nullable();
        });
    }
};
