<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mandatory_announcements')) {
            Schema::create('mandatory_announcements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('body');
                $table->string('priority', 20)->default('important')->index();
                $table->string('target_type', 30)->default('all_social_workers')->index();
                $table->boolean('is_blocking')->default(true)->index();
                $table->unsignedSmallInteger('min_reading_seconds')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('mandatory_announcement_recipients')) {
            Schema::create('mandatory_announcement_recipients', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('announcement_id')->constrained('mandatory_announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('social_worker_id')->nullable()->constrained('social_workers')->nullOnDelete();
                $table->timestamp('read_at')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id']);
                $table->index(['user_id', 'read_at']);
                $table->index(['social_worker_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_announcement_recipients');
        Schema::dropIfExists('mandatory_announcements');
    }
};
