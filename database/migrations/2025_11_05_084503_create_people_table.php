<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 6)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->nullable();
            $table->string('national_id', 10)->unique();

            // اطلاعات تولد
            $table->integer('birth_day');
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->integer('birth_year')->nullable();

            $table->string('birth_date_full', 10)
                ->nullable()
                ->comment('تاریخ کامل تولد شمسی - فرمت: YYYY/MM/DD');

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
            $table->unsignedBigInteger('disability_type_id')->nullable();
            $table->foreign('disability_type_id')
                ->references('id')
                ->on('disability_types')
                ->nullOnDelete();
            $table->text('disability_description')->nullable();

            // توضیحات مهارت
            $table->text('skills_description')->nullable();

            // زمان‌بندی
            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index(['last_name', 'first_name'], 'idx_people_name');
            $table->index('national_id', 'idx_people_national_id');
            $table->index('has_disability', 'idx_people_has_disability');

            // ایندکس تک‌ستونی برای فیلترینگ سریع
            $table->index('birth_day',   'idx_people_birth_day');
            $table->index('birth_month', 'idx_people_birth_month');
            $table->index('birth_year',  'idx_people_birth_year');
            $table->index('birth_date_full', 'idx_people_birth_date_full');

            // ایندکس ترکیبی
            // جستجوی تولد امروز (ماه + روز)
            $table->index(['birth_month', 'birth_day'],
                'idx_people_birth_month_day');
            // جستجوی تاریخ کامل تولد (سال + ماه + روز)
            $table->index(
                ['birth_year', 'birth_month', 'birth_day'],
                'idx_people_full_birth_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // حذف ایندکس‌های ترکیبی
            $table->dropIndex('idx_people_birth_month_day');
            $table->dropIndex('idx_people_full_birth_date');

            // حذف ایندکس‌های تک‌ستونی
            $table->dropIndex('idx_people_birth_day');
            $table->dropIndex('idx_people_birth_month');
            $table->dropIndex('idx_people_birth_year');
            $table->dropIndex('idx_people_birth_date_full');

            // سایر ایندکس‌ها
            $table->dropIndex('idx_people_name');
            $table->dropIndex('idx_people_national_id');
            $table->dropIndex('idx_people_has_disability');
        });

        Schema::dropIfExists('people');
    }
};
