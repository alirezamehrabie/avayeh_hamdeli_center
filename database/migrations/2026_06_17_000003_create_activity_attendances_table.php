<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('qr_identity_id')->nullable()->constrained('qr_identities')->nullOnDelete();
            $table->string('status', 32)->default('present');
            $table->string('registration_method', 32)->default('manual');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'person_id'], 'activity_attendances_activity_person_unique');
            $table->index(['activity_id', 'status'], 'activity_attendances_activity_status_index');
            $table->index(['person_id', 'checked_in_at'], 'activity_attendances_person_checked_in_index');
            $table->index('qr_identity_id', 'activity_attendances_qr_identity_index');
            $table->index(['recorded_by', 'created_at'], 'activity_attendances_recorder_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_attendances');
    }
};
