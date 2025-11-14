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
            $table->foreignId('person_id')->unique()->constrained('people')->onDelete('cascade');
            $table->integer('children_count')->nullable();
            $table->integer('children_in_house')->nullable();
            $table->boolean('any_family_employed')->default(false)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('occupation')->nullable();
            $table->string('job_type')->nullable();
            $table->string('insurance_status')->nullable();
            $table->string('other_insurance')->nullable();
            $table->string('divorced_child_at_home')->nullable(); // دختر، پسر، هیچکدام
            $table->unsignedBigInteger('average_income')->nullable();
            $table->boolean('has_vehicle')->default(false);
            $table->string('vehicle_type')->nullable();
            $table->string('guardian_phone_number', 11)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
