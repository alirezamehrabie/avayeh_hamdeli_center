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
        Schema::create('social_workers', function (Blueprint $table) {
            $table->id();

            // کد مددکاری (الزامی و یونیک)
            $table->unsignedInteger('worker_code')->unique()->nullable();

            // نام و نام خانوادگی
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->nullable(); // یک ستون معمولی و ساده

            // شناسه‌ها
            $table->string('national_id', 10)->unique()->nullable();
            $table->string('id_number')->nullable(); // شماره شناسنامه

            // مشخصات تولد
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('birth_date_full')->nullable();

            // اطلاعات شخصی و خانوادگی
            $table->string('education')->nullable();
            $table->unsignedTinyInteger('family_members_count')->default(0);
            $table->string('mobile')->nullable();

            // مسیر عکس
            $table->string('photo_path')->nullable();

            // تاریخ شروع همکاری
            $table->unsignedTinyInteger('start_day')->nullable();
            $table->unsignedTinyInteger('start_month')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->string('start_date_full')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('occupation_id')->nullable()->constrained('occupations')->nullOnDelete();


            // منطقه تحت پوشش
            $table->string('covered_area')->nullable();

            // آمار تحت پوشش
            $table->unsignedInteger('covered_people_count')->default(0);
            $table->unsignedInteger('covered_households_count')->default(0);
            $table->unsignedInteger('covered_children_count')->default(0);

            // همکار علی‌البدل
            $table->string('substitute_first_name')->nullable();
            $table->string('substitute_last_name')->nullable();
            $table->string('substitute_mobile')->nullable();

            // timestamps برای ثبت زمان ایجاد و به‌روزرسانی
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_workers');
    }
};
