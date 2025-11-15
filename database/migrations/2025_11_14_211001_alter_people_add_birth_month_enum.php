<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // 1. اضافه‌کردن ستون موقت ENUM
            $table->enum('birth_month_tmp', [
                'فروردین','اردیبهشت','خرداد','تیر',
                'مرداد','شهریور','مهر','آبان',
                'آذر','دی','بهمن','اسفند',
            ])
                ->nullable()
                ->after('birth_day')
                ->comment('ماه تولد (موقت)');
        });

        // 2. کپی داده‌ها از ستون قدیمی به ستون موقت
        //    در صورتی که مقادیر در ستون قدیمی دقیقاً همان متون بالا باشند:
        DB::table('people')->whereNotNull('birth_month')->update([
            'birth_month_tmp' => DB::raw('birth_month')
        ]);

        Schema::table('people', function (Blueprint $table) {
            // 3. حذف ستون رشته‌ای قدیمی
            $table->dropColumn('birth_month');
            // 4. تغییر نام ستون موقت به birth_month
            $table->renameColumn('birth_month_tmp', 'birth_month');
            // 5. ایندکس کردن برای سرعت
            $table->index('birth_month');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // در ری‌داون برعکس عمل کنید:
            $table->dropIndex(['birth_month']);
            $table->string('birth_month')->nullable()->after('birth_day');
        });

        // بازگرداندن مقادیر (اختیاری)
        DB::table('people')->whereNotNull('birth_month')->update([
            'birth_month_tmp' => DB::raw('birth_month')
        ]);

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('birth_month');
            $table->renameColumn('birth_month_tmp', 'birth_month');
        });
    }
};
