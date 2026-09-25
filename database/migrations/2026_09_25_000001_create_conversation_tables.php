<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table): void {
                $table->id();
                $table->string('subject', 190);
                $table->string('status', 20)->default('pending')->index();
                $table->morphs('sender');
                $table->string('sender_name');
                $table->string('sender_role', 20);
                $table->string('sender_code', 20)->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->morphs('sender');
                $table->string('sender_name')->nullable();
                $table->boolean('is_from_staff')->default(false);
                $table->text('body');
                $table->timestamp('staff_read_at')->nullable();
                $table->timestamp('member_read_at')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'created_at']);
                $table->index(['is_from_staff', 'staff_read_at']);
            });
        }

        if (! Schema::hasTable('message_attachments')) {
            Schema::create('message_attachments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->string('original_name');
                $table->string('path');
                $table->string('mime', 50);
                $table->unsignedInteger('size');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
