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
            // افزودن ستون full_name بعد از last_name
            // nullable می گذاریم تا برای رکوردهای قدیمی مشکلی پیش نیاید
            $table->string('full_name')->after('last_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
