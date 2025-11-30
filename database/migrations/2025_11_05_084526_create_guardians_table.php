<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();

            // شناسه فرد (یکتا) و رابطه با جدول people
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->onDelete('cascade');


            // ===== افزودن فیلدهای جدید تاریخ تولد شمسی =====
            $table->unsignedTinyInteger('guardian_birth_day')
                ->nullable();
            $table->unsignedTinyInteger('guardian_birth_month')
                ->nullable();
            $table->unsignedSmallInteger('guardian_birth_year')
                ->nullable();
            $table->string('guardian_birth_date_full', 10)
                ->nullable();

            $table->integer('children_count')->nullable();
            $table->integer('children_in_house')->nullable();

            // آیا خانواده کسی مشغول به کار است؟
            $table->boolean('any_family_employed')
                ->default(false)
                ->nullable();


            // شغل (ارجاع به occupations)
            $table->foreignId('occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // نوع شغل (ارجاع به job_types)
            $table->foreignId('job_type_id')
                ->nullable()
                ->constrained('job_types')
                ->nullOnDelete();


            // وضعیت بیمه
            $table->boolean('insurance_status')->default(false);

            // نوع بیمه (ارجاع به insurance_types)
            $table->foreignId('insurance_type_id')
                ->nullable()
                ->constrained('insurance_types')
                ->nullOnDelete();

            // کودک یا فرزند طلاق‌گرفته در منزل
            $table->string('divorced_child_at_home')
                ->default('ندارد');

            // میانگین درآمد
            $table->unsignedBigInteger('average_income')->nullable();

            // وضعیت داشتن خودرو
            $table->boolean('has_vehicle')->default(false);

            // نوع خودرو (ارجاع به vehicle_types)
            $table->foreignId('vehicle_type_id')
                ->nullable()
                ->constrained('vehicle_types')
                ->nullOnDelete();

            // شماره تماس سرپرست
            $table->string('guardian_phone_number', 11)->nullable();

            $table->timestamps();

            // ===== ایندکس‌ها برای بهینه‌سازی جستجو و گزارش‌گیری =====
            $table->index('guardian_birth_year', 'idx_guardians_birth_year');
            $table->index('guardian_birth_month', 'idx_guardians_birth_month');
            $table->index(['guardian_birth_month', 'guardian_birth_day'], 'idx_guardians_birth_month_day'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
