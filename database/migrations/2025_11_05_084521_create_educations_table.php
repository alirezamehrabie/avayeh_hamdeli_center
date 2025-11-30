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
        Schema::create('educations', function (Blueprint $table) {
            $table->id();

            // هر فرد یک رکورد تحصیلی یکتا دارد
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->onDelete('cascade');

            $table->boolean('is_studying')
                ->default(true)
                ->nullable();

            $table->string('school_name')
                ->nullable();

            $table->string('major')
                ->nullable();

            // سطح تحصیلات
            $table->foreignId('education_level_id')
                ->nullable()
                ->constrained('education_levels')
                ->nullOnDelete();

            $table->text('drop_reason')
                ->nullable();

            $table->boolean('works_alongside_study')
                ->default(false)
                ->nullable();

            $table->unsignedBigInteger('monthly_income')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
