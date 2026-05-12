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
            $table->unsignedInteger('guardian_code')->unique();

            $table->string('national_code', 10)->unique()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();


            // ===== افزودن فیلدهای جدید تاریخ تولد شمسی =====
            $table->unsignedTinyInteger('guardian_birth_day')->nullable();
            $table->unsignedTinyInteger('guardian_birth_month')->nullable();
            $table->unsignedSmallInteger('guardian_birth_year')->nullable();
            $table->string('guardian_birth_date_full', 10)->nullable();


            $table->unsignedInteger('children_count')->default(0);
            $table->unsignedInteger('children_in_house')->nullable()->default(0);

            // وضعیت اشتغال اعضای خانواده
            $table->boolean('any_family_employed')->nullable();
            $table->text('any_family_employed_description')->nullable();

            // درآمد - فقط یکبار تعریف شده
            $table->unsignedBigInteger('average_income')->nullable()->comment('Amount in Rials');
            $table->unsignedTinyInteger('economic_decile')->nullable()->comment('1 to 10');

            // شغل و نوع شغل
            $table->foreignId('occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('job_type_id')
                ->nullable()
                ->constrained('job_types')
                ->nullOnDelete();


            // وضعیت بیمه
            $table->boolean('insurance_status')
                ->default(false);
            $table->foreignId('insurance_type_id')
                ->nullable()
                ->constrained('insurance_types')
                ->nullOnDelete();

            // کودک یا فرزند طلاق‌گرفته در منزل
            $table->string('divorced_child_at_home')
                ->default('none');

            // وضعیت داشتن خودرو
            $table->boolean('has_vehicle')->nullable();

            $table->foreignId('vehicle_type_id')
                ->nullable()
                ->constrained('vehicle_types')
                ->nullOnDelete();

            $table->string('vehicle_ownership_type')
                ->nullable();

            // شماره تماس سرپرست
            $table->string('guardian_phone_number', 11)
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ===== ایندکس‌ها برای بهینه‌سازی جستجو =====
            $table->index('guardian_birth_year', 'idx_guardians_birth_year');
            $table->index('guardian_birth_month', 'idx_guardians_birth_month');
            $table->index(
                ['guardian_birth_month', 'guardian_birth_day'],
                'idx_guardians_birth_month_day'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
