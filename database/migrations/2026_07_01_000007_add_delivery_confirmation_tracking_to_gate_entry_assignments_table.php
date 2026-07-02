<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gate_entry_assignments', function (Blueprint $table) {
            // Captures the moment (and operator) a Delivery Gate confirmation happens, independent of
            // `updated_at` — which gets overwritten again when the Exit Gate later finalizes the row.
            // Without this, the Gate Reports feature has no reliable "delivered at / by" once an item exits.
            $table->timestamp('delivered_at')->nullable()->after('assigned_at');
            $table->foreignId('delivered_by')->nullable()->after('delivered_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gate_entry_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivered_by');
            $table->dropColumn('delivered_at');
        });
    }
};
