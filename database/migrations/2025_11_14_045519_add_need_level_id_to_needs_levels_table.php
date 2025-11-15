<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs_levels', function (Blueprint $table) {
            // افزودن کلید خارجی
            $table->foreignId('need_level_id')
                ->nullable()
                ->after('person_id')
                ->constrained('need_level_types')
                ->nullOnDelete();

            // در نهایت حذف ستون متنی قدیمی
            $table->dropColumn('need_level');
        });
    }

    public function down(): void
    {
        Schema::table('needs_levels', function (Blueprint $table) {
            $table->string('need_level')->nullable(); // بازیابی ستون قبلی
            $table->dropConstrainedForeignId('need_level_id');
        });
    }
};
