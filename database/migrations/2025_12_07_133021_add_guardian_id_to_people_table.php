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
            $table->foreignId('guardian_id')
                ->nullable() // سرپرست می‌تواند در ابتدا null باشد تا زمانی که ثبت شود
                ->constrained('guardians') // ارجاع به جدول guardians
                ->onDelete('set null'); // در صورت حذف سرپرست، guardian_id در people به null تغییر کند
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['guardian_id']); // حذف کلید خارجی
            $table->dropColumn('guardian_id'); // حذف ستون
        });
    }
};
