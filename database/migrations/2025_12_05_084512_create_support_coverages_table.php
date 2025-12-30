<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_coverages', function (Blueprint $table) {
            $table->id();

            // کلید خارجی به جدول people
            $table->foreignId('person_id')
                ->constrained('people')
                ->onDelete('cascade');

            $table->string('organization_type')->nullable();

            $table->string('support_card_image')->nullable();

            // افزودن فیلدهای جدید شمسی (روز، ماه، سال)
            $table->tinyInteger('coverage_start_day')->nullable();
            $table->tinyInteger('coverage_start_month')->nullable();
            $table->smallInteger('coverage_start_year')->nullable();

            // افزودن تاریخ کامل شمسی به صورت رشته "YYYY/MM/DD"
            // جهت جستجو و نمایش
            $table->string('coverage_start_date', 10)
                ->nullable()
                ->comment('فرمت کامل تاریخ شمسی YYYY/MM/DD جهت جستجو و نمایش');

            $table->boolean('active_status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_coverages');
    }
};
