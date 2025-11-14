<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->onDelete('cascade');
            $table->boolean('is_studying')->default(true)->nullable();
            $table->string('school_name')->nullable();
            $table->string('major')->nullable();
            $table->string('education_level')->nullable();
            $table->text('drop_reason')->nullable();
            $table->boolean('works_alongside_study')->default(false)->nullable();
            $table->unsignedBigInteger('monthly_income')->nullable();
            $table->text('talent_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
