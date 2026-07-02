<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_operator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Constraint to ensure one-to-one assignment per activity-operator pair
            $table->unique(['activity_id', 'user_id'], 'unique_activity_operator');

            // Indexes for efficient querying
            $table->index('activity_id', 'idx_activity_operator_activity');
            $table->index('user_id', 'idx_activity_operator_user');
            $table->index('assigned_by', 'idx_activity_operator_assigned_by');
            $table->index('assigned_at', 'idx_activity_operator_assigned_at');
            $table->index('deleted_at', 'idx_activity_operator_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_operator_assignments');
    }
};
