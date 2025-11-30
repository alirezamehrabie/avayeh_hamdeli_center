<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // مرحله ۱: حذف ستون فعلی full_name (اگر وجود دارد)
        if (Schema::hasColumn('people', 'full_name')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropColumn('full_name');
            });
        }

        // مرحله ۲: ایجاد ستون مجازی (Generated Column)
        // نوع STORED یعنی مقدار در دیسک ذخیره می‌شود و قابل ایندکس‌گذاری است
        DB::statement("
            ALTER TABLE people
            ADD COLUMN full_name VARCHAR(511)
            GENERATED ALWAYS AS (CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))
            STORED
            AFTER last_name
        ");

        // مرحله ۳: ایجاد ایندکس روی ستون مجازی (برای جستجوی سریع)
        Schema::table('people', function (Blueprint $table) {
            $table->index('full_name', 'idx_people_full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف ایندکس
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_full_name');
        });

        // حذف ستون مجازی
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });

        // بازگرداندن ستون معمولی
        Schema::table('people', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('last_name');
        });

        // پر کردن دوباره مقادیر
        DB::statement("
            UPDATE people
            SET full_name = CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))
        ");
    }
};
