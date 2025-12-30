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
            $table->unsignedInteger('worker_code')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name');


            $table->string('national_id', 10)->unique()->nullable();
            $table->string('id_number')->nullable();
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('birth_date_full')->nullable();
            $table->unsignedTinyInteger('family_members_count')->default(0);
            $table->string('mobile')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedTinyInteger('start_day')->nullable();
            $table->unsignedTinyInteger('start_month')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->string('start_date_full')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('occupation_id')->nullable()->constrained('occupations')->nullOnDelete();
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->nullOnDelete();
            $table->unsignedInteger('covered_people_count')->default(0);
            $table->unsignedInteger('covered_households_count')->default(0);
            $table->unsignedInteger('covered_children_count')->default(0);
            $table->string('substitute_first_name')->nullable();
            $table->string('substitute_last_name')->nullable();
            $table->string('substitute_mobile')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // حالا ستون مجازی را بدون تداخل نام اضافه می‌کنیم
        DB::statement("
        ALTER TABLE social_workers
        ADD COLUMN full_name VARCHAR(511)
        GENERATED ALWAYS AS (
            CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))
        ) STORED
        AFTER last_name
    ");

        Schema::table('social_workers', function (Blueprint $table) {
            $table->index('full_name', 'idx_social_workers_full_name');
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
