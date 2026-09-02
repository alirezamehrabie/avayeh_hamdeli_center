<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_entry_delivery_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            // The Entry Gate operator declared the items go to somebody other than the beneficiary.
            $table->boolean('is_proxy_delivery')->default(false);
            // mother | father | group_leader | other — see GateEntryDeliveryRecipient::TYPE_OPTIONS.
            $table->string('recipient_type', 30)->nullable();
            // Free-text name/details, only meaningful when recipient_type is "other".
            $table->string('recipient_name')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One declaration per subject per service (person OR guardian, never both), the same grain
            // as gate_entry_field_values so the gate can upsert it straight from the scanned subject.
            $table->unique(['service_id', 'person_id'], 'gate_entry_recipient_service_person_unique');
            $table->unique(['service_id', 'guardian_id'], 'gate_entry_recipient_service_guardian_unique');
            // Reporting entry point: "which handoffs at this service went to a non-beneficiary".
            $table->index(['service_id', 'is_proxy_delivery'], 'gate_entry_recipient_service_proxy_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_entry_delivery_recipients');
    }
};
