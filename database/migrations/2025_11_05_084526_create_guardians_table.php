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
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->onDelete('cascade');

            $table->integer('children_count')->nullable();
            $table->integer('children_in_house')->nullable();
            $table->boolean('any_family_employed')->default(false)->nullable();
            $table->date('birth_date')->nullable();

            $table->foreignId('occupation_id')
                ->nullable()
                ->constrained('occupations')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // تبدیل job_type به کلید خارجی
            $table->foreignId('job_type_id')
                ->nullable()
                ->constrained('job_types')
                ->nullOnDelete();

            $table->boolean('insurance_status')->default(false);

            // کلید خارجی نوع بیمه
            $table->foreignId('insurance_type_id')
                ->nullable()
                ->constrained('insurance_types')
                ->nullOnDelete();

            $table->string('divorced_child_at_home')->nullable();
            $table->unsignedBigInteger('average_income')->nullable();

            // وضعیت وجود خودرو به صورت بولین
            $table->boolean('has_vehicle')->default(false);

            // کلید خارجی نوع خودرو
            $table->foreignId('vehicle_type_id')
                ->nullable()
                ->constrained('vehicle_types')
                ->nullOnDelete();

            $table->string('guardian_phone_number', 11)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
