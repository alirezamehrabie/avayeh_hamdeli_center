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
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('person_code', 8)->unique();
            $table->string('national_id', 10)->unique();

            $table->string('first_name');
            $table->string('last_name');

            $table->string('full_name', 511)
                ->storedAs("CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))");

            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();

            // Generating full date safely instead of redundant storage
            $table->string('birth_date_full', 10)->nullable()
                ->virtualAs("CONCAT_WS('/', birth_year, LPAD(birth_month, 2, '0'), LPAD(birth_day, 2, '0'))")
                ->comment('YYYY/MM/DD - Auto Generated');
            $table->index('birth_date_full', 'idx_people_birth_date_full');

            // اطلاعات والدین
            $table->string('father_name')->nullable();
            $table->char('father_national_id', 10)->nullable();
            $table->char('mother_national_id', 10)->nullable();
            $table->string('phone_number', 20)->nullable();

            // وضعیت فرد
            $table->enum('gender', ['male', 'female']);
            $table->enum('role', ['child', 'guardian']);
            $table->enum('sadaat_status', ['sadaat', 'general'])->nullable();

            $table->foreignId('sadaat_relation_id')
                ->nullable()
                ->constrained('sadaat_relations')
                ->nullOnDelete();
            $table->index('sadaat_relation_id', 'idx_people_sadaat_relation');

            // مددکار اجتماعی
            $table->foreignId('social_worker_id')
                ->nullable()
                ->constrained('social_workers')
                ->nullOnDelete();
            $table->index('social_worker_id', 'idx_people_social_worker');


            $table->foreignId('guardian_id')
                ->nullable()
                ->constrained('guardians')
                ->nullOnDelete();
            $table->index('guardian_id', 'idx_people_guardian');


            $table->string('photo_id_card')->nullable();
            $table->string('photo_birth_certificate')->nullable();
            $table->string('profile_photo')->nullable();

            $table->boolean('has_disability')->default(false);
            $table->foreignId('disability_type_id')->nullable()->constrained('disability_types')->nullOnDelete();
            $table->index('disability_type_id', 'idx_people_disability_type');
            $table->text('disability_description')->nullable();

            $table->text('skills_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name'], 'idx_people_name');
            $table->index('full_name', 'idx_people_full_name');
            $table->index('has_disability', 'idx_people_has_disability');


            // Composite index covers year, year+month, and year+month+day searches
            $table->index(['birth_year', 'birth_month', 'birth_day'], 'idx_people_birth_composite');

            // Optional: Only if you have a "Upcoming Birthdays" feature
            $table->index(['birth_month', 'birth_day'], 'idx_people_birth_month_day');

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_birth_month_day');
            $table->dropIndex('idx_people_name');
            $table->dropIndex('idx_people_full_name');
            $table->dropIndex('idx_people_birth_composite');
            $table->dropIndex('idx_people_has_disability');
            $table->dropIndex('idx_people_sadaat_relation');
            $table->dropIndex('idx_people_social_worker');
            $table->dropIndex('idx_people_guardian');
            $table->dropIndex('idx_people_disability_type');
            $table->dropIndex('idx_people_birth_date_full');
        });

        // حذف کل جدول people
        Schema::dropIfExists('people');
    }
};
