<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * تبدیل guardian_birth_date به سه فیلد جداگانه شمسی
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // ═══════════════════════════════════════════════════════════
            // حذف فیلد قدیمی
            // ═══════════════════════════════════════════════════════════
            if (Schema::hasColumn('guardians', 'guardian_birth_date')) {
                $table->dropColumn('guardian_birth_date');
            }

            // ═══════════════════════════════════════════════════════════
            // افزودن فیلدهای جدید شمسی
            // ═══════════════════════════════════════════════════════════

            // روز تولد (۱ تا ۳۱)
            $table->unsignedTinyInteger('guardian_birth_day')->nullable()->after('person_id');

            // ماه تولد (۱ تا ۱۲)
            $table->unsignedTinyInteger('guardian_birth_month')->nullable()->after('guardian_birth_day');

            // سال تولد شمسی (مثلاً ۱۳۵۰)
            $table->unsignedSmallInteger('guardian_birth_year')->nullable()->after('guardian_birth_month');

            // تاریخ کامل به فرمت رشته‌ای برای نمایش (مثلاً "1350/06/15")
            $table->string('guardian_birth_date_full', 10)->nullable()->after('guardian_birth_year');

            // ═══════════════════════════════════════════════════════════
            // ایندکس‌گذاری برای بهینه‌سازی جستجو و گزارش‌گیری
            // ═══════════════════════════════════════════════════════════

            // ایندکس روی سال (برای فیلتر بر اساس بازه سنی)
            $table->index('guardian_birth_year', 'idx_guardians_birth_year');

            // ایندکس روی ماه (برای گزارش تولدهای ماهانه)
            $table->index('guardian_birth_month', 'idx_guardians_birth_month');

            // ایندکس ترکیبی (برای جستجوی تولد امروز/این ماه)
            $table->index(['guardian_birth_month', 'guardian_birth_day'], 'idx_guardians_birth_month_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // حذف ایندکس‌ها
            $table->dropIndex('idx_guardians_birth_year');
            $table->dropIndex('idx_guardians_birth_month');
            $table->dropIndex('idx_guardians_birth_month_day');

            // حذف فیلدهای جدید
            $table->dropColumn([
                'guardian_birth_day',
                'guardian_birth_month',
                'guardian_birth_year',
                'guardian_birth_date_full'
            ]);

            // بازگرداندن فیلد قدیمی
            $table->date('guardian_birth_date')->nullable()->after('person_id');
        });
    }
};
