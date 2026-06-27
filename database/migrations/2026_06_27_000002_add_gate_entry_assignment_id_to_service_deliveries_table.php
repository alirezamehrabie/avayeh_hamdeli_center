<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            // Links a gate-channel delivery back to the GateEntryAssignment it was finalized from.
            // The unique index makes the ledger idempotent: at most one delivery row per assignment,
            // so a re-finalization can never create a duplicate. Other channels leave this NULL, and
            // MySQL allows multiple NULLs in a unique index, so they are unaffected.
            $table->unsignedBigInteger('gate_entry_assignment_id')->nullable()->after('service_category_id');

            $table->unique('gate_entry_assignment_id', 'sd_gate_entry_assignment_unique');

            $table->foreign('gate_entry_assignment_id')
                ->references('id')
                ->on('gate_entry_assignments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->dropForeign(['gate_entry_assignment_id']);
            $table->dropUnique('sd_gate_entry_assignment_unique');
            $table->dropColumn('gate_entry_assignment_id');
        });
    }
};
