<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manager_notification_preferences')) {
            Schema::create('manager_notification_preferences', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
                $table->string('event_key', 100);
                $table->boolean('enabled')->default(false);
                // all: هر کاربری | roles: نقش‌های انتخاب‌شده | users: کاربران انتخاب‌شده
                $table->string('target_type', 20)->default('all');
                $table->json('target_roles')->nullable();
                $table->json('target_user_ids')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['manager_id', 'event_key']);
                $table->index(['event_key', 'enabled']);
            });
        }

        if (! Schema::hasTable('manager_notifications')) {
            Schema::create('manager_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
                $table->string('event_key', 100);
                $table->string('title');
                $table->text('message');
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name')->nullable();
                $table->string('actor_role', 50)->nullable();
                $table->nullableMorphs('subject');
                $table->json('context')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['manager_id', 'read_at']);
                $table->index(['manager_id', 'event_key']);
                $table->index(['manager_id', 'created_at']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_notifications');
        Schema::dropIfExists('manager_notification_preferences');
    }
};
