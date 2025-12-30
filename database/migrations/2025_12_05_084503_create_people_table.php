<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ۱. ایجاد جدول اصلی people
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('person_code', 6)->unique();
            $table->string('first_name');
            $table->string('last_name');
            // ستون full_name در مرحله بعدی به صورت مجازی اضافه می‌شود
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

            // مددکار اجتماعی
            $table->foreignId('social_worker_id')
                ->nullable()
                ->constrained('social_workers')
                ->nullOnDelete();


            $table->foreignId('guardian_id')
                ->nullable()
                ->constrained('guardians')
                ->onDelete('set null');

            // مستندات تصویری
            $table->string('photo_id_card')->nullable();
            $table->string('photo_birth_certificate')->nullable();
            $table->string('profile_photo')->nullable();

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

            // ایندکس‌های متنی و عددی
            $table->index(['last_name', 'first_name'], 'idx_people_name');
            $table->index('national_id', 'idx_people_national_id');
            $table->index('has_disability', 'idx_people_has_disability');
            $table->index('birth_day',   'idx_people_birth_day');
            $table->index('birth_month', 'idx_people_birth_month');
            $table->index('birth_year',  'idx_people_birth_year');
            $table->index('birth_date_full', 'idx_people_birth_date_full');
            $table->index(
                ['birth_month', 'birth_day'],
                'idx_people_birth_month_day'
            );
            $table->index(
                ['birth_year', 'birth_month', 'birth_day'],
                'idx_people_full_birth_date'
            );
        });

        // ۲. تعریف ستون مجازی full_name و ایندکس آن
        DB::statement("
            ALTER TABLE people
            ADD COLUMN full_name VARCHAR(511)
            GENERATED ALWAYS AS (
                CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))
            ) STORED
            AFTER last_name
        ");

        Schema::table('people', function (Blueprint $table) {
            $table->index('full_name', 'idx_people_full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_birth_month_day');
            $table->dropIndex('idx_people_full_birth_date');
            $table->dropIndex('idx_people_birth_day');
            $table->dropIndex('idx_people_birth_month');
            $table->dropIndex('idx_people_birth_year');
            $table->dropIndex('idx_people_birth_date_full');
            $table->dropIndex('idx_people_name');
            $table->dropIndex('idx_people_national_id');
            $table->dropIndex('idx_people_has_disability');
            $table->dropIndex('idx_people_full_name');
        });

        // حذف کل جدول people
        Schema::dropIfExists('people');
    }
};
