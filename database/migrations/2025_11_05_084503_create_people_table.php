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
            $table->string('national_id', 10)->unique();
            $table->integer('birth_day');
            $table->unsignedTinyInteger('birth_month');
            $table->integer('birth_year')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_national_id', 10)->nullable();
            $table->string('mother_national_id', 10)->nullable();
            $table->enum('gender', ['مرد', 'زن']);
            $table->enum('role', ['فرزند', 'سرپرست']);

            // 🧿 فیلد جدید سادات / عام
            $table->enum('sadaat_status', ['سادات', 'عام'])->nullable();
            $table->enum('sadaat_relation', ['سادات موسوی','سادات حسنی','سادات حسینی','سادات طباطبائی','سادات هاشمی'])->nullable();


            // 👨‍👧 نوع سرپرست (پدر خانواده / مادر خانواده / سایر)
            $table->enum('guardian_role', ['پدر خانواده', 'مادر خانواده', 'سایر'])->nullable();

            $table->unsignedBigInteger('social_worker_id')->nullable();
            $table->string('photo_id_card')->nullable();
            $table->string('photo_birth_certificate')->nullable();
            $table->text('photo_live_capture')->nullable();
            $table->boolean('has_disability')->default(false);
            $table->string('disability_type')->nullable();
            $table->text('disability_description')->nullable();
            $table->json('skills')->nullable();
            $table->text('skills_description')->nullable();
            $table->timestamps();

            $table->foreign('social_worker_id')
                ->references('id')->on('social_workers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
