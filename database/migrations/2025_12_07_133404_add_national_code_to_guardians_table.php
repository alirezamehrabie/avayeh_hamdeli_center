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
        Schema::table('guardians', function (Blueprint $table) {
            // اضافه کردن ستون national_code
            // آن را nullable می‌کنیم چون ممکن است همیشه هنگام ثبت اولیه سرپرست کد ملی موجود نباشد (گرچه در این فرم required است)
            // و همچنین آن را unique می‌کنیم تا کد ملی تکراری نباشد.
            $table->string('national_code', 10)->unique()->nullable()->after('id'); // بعد از ستون id قرار می‌گیرد
            $table->string('first_name')->nullable()->after('national_code');
            $table->string('last_name')->nullable()->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // هنگام بازگشت migration، ستون‌ها را حذف می‌کنیم
            $table->dropColumn(['national_code', 'first_name', 'last_name']);
        });
    }
};
