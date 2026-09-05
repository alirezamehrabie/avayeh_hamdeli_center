<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_delivery_cancellations', function (Blueprint $table) {
            // Immutable audit trail for ServiceDelivery ledger rows purged at the Exit Gate.
            // The ledger row itself is force-deleted (to free its unique gate_entry_assignment_id
            // slot for a clean re-finalization), so the referenced entity ids are plain columns
            // WITHOUT foreign keys: a cancellation record must survive any later purge of the
            // service, subject or assignment it points at.
            $table->id();
            $table->unsignedBigInteger('service_delivery_id')->nullable();
            $table->unsignedBigInteger('gate_entry_assignment_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('service_category_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->decimal('delivered_quantity', 12, 2)->nullable();
            $table->integer('delivered_total_value')->nullable();
            // The original ledger row's delivery date, kept for range queries without parsing JSON.
            $table->date('delivered_at')->nullable();
            // Full raw attributes of the ledger row at the moment of cancellation.
            $table->json('delivery_snapshot')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            // Nullable on purpose: a non-nullable first TIMESTAMP column gets an implicit
            // ON UPDATE CURRENT_TIMESTAMP on older MySQL defaults.
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'canceled_at'], 'sdc_service_canceled_index');
            $table->index('gate_entry_assignment_id', 'sdc_assignment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_delivery_cancellations');
    }
};
