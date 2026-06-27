<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_entry_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            // Map of { service_entry_field_id: value }; education_level holds an education_levels id.
            $table->json('values')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // One value row per subject per service (person OR guardian, never both).
            $table->unique(['service_id', 'person_id'], 'gate_entry_field_service_person_unique');
            $table->unique(['service_id', 'guardian_id'], 'gate_entry_field_service_guardian_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_entry_field_values');
    }
};
