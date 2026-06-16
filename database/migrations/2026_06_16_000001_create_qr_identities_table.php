<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_identities', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->char('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->string('public_code', 32)->nullable()->unique();
            $table->string('status', 20)->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'qr_identities_subject_index');
            $table->index(['subject_type', 'subject_id', 'status'], 'qr_identities_subject_status_index');
        });

        Schema::create('qr_identity_scan_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qr_identity_id')->nullable()->constrained('qr_identities')->nullOnDelete();
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome', 32);
            $table->string('context', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at'], 'qr_scan_subject_created_index');
            $table->index(['user_id', 'created_at'], 'qr_scan_user_created_index');
            $table->index('outcome', 'qr_scan_outcome_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_identity_scan_logs');
        Schema::dropIfExists('qr_identities');
    }
};
