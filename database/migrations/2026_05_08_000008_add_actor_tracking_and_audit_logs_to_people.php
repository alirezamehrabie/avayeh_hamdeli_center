<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('people', 'social_worker_id') ? 'social_worker_id' : 'guardian_id';

            $table->foreignId('created_by')
                ->nullable()
                ->after($afterColumn)
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('created_by', 'idx_people_created_by');
            $table->index('updated_by', 'idx_people_updated_by');
        });

        Schema::create('beneficiary_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20); // created|updated|deleted
            $table->json('changed_fields')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'created_at'], 'idx_beneficiary_audit_person_created_at');
            $table->index('action', 'idx_beneficiary_audit_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_audit_logs');

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_created_by');
            $table->dropIndex('idx_people_updated_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
