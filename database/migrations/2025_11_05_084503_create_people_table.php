<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 4)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->nullable();
            $table->string('national_id', 10)->unique();

            // اطلاعات تولد
            $table->integer('birth_day');
            $table->enum('birth_month', [
                'فروردین','اردیبهشت','خرداد','تیر',
                'مرداد','شهریور','مهر','آبان',
                'آذر','دی','بهمن','اسفند',
            ])->nullable()->comment('ماه تولد');
            $table->integer('birth_year')->nullable();


            // اطلاعات والدین
            $table->string('father_name')->nullable();
            $table->string('father_national_id', 10)->nullable();
            $table->string('mother_national_id', 10)->nullable();

            // وضعیت فرد
            $table->enum('gender', ['مرد', 'زن']);
            $table->enum('role', ['فرزند', 'سرپرست']);

            // وضعیت سادات یا عام
            $table->enum('sadaat_status', ['سادات', 'عام'])->nullable();
            $table->foreignId('sadaat_relation_id')
                ->nullable()
                ->constrained('sadaat_relations')
                ->nullOnDelete();


            // کلید خارجی مددکار اجتماعی
            $table->foreignId('social_worker_id')
                ->nullable()
                ->constrained('social_workers')
                ->nullOnDelete();

            // مستندات تصویری
            $table->string('photo_id_card')->nullable();
            $table->string('photo_birth_certificate')->nullable();
            $table->text('photo_live_capture')->nullable();


            // معلولیت
            $table->boolean('has_disability')->default(false);

            $table->unsignedBigInteger('disability_type_id')
                ->nullable();
            $table->foreign('disability_type_id')
                ->references('id')
                ->on('disability_types')
                ->nullOnDelete();

            $table->text('disability_description')->nullable();


            // توضیحات مهارت
            $table->text('skills_description')->nullable();

            // زمان بندی
            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index(['last_name', 'first_name']);
            $table->index('national_id');
            $table->index('birth_month');
            $table->index('has_disability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
