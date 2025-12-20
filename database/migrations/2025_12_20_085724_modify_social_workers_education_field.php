<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_workers', function (Blueprint $table) {

            // اضافه کردن کلید خارجی جدید
            // از nullable استفاده می‌کنیم تا اگر فعلاً مقطعی ثبت نشد خطا ندهد
            $table->unsignedTinyInteger('academic_level_id')->nullable()->after('mobile');

            // تعریف رابطه Foreign Key
            $table->foreign('academic_level_id')
                ->references('id')
                ->on('academic_levels')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('social_workers', function (Blueprint $table) {
            $table->dropForeign(['academic_level_id']);
            $table->dropColumn('academic_level_id');
            $table->string('education')->nullable();
        });
    }
};
