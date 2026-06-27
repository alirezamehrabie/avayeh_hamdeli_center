<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_entry_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->string('national_id')->nullable();
            $table->string('full_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['service_category_id', 'person_id'], 'gate_entry_category_person_unique');
            $table->unique(['service_category_id', 'guardian_id'], 'gate_entry_category_guardian_unique');
            $table->index('service_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_entry_assignments');
    }
};
