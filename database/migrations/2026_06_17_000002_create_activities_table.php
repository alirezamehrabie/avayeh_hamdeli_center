<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('activity_type')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 32)->default('draft');
            $table->text('status_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'activities_status_index');
            $table->index('activity_type', 'activities_activity_type_index');
            $table->index('starts_at', 'activities_starts_at_index');
            $table->index(['status', 'starts_at'], 'activities_status_starts_at_index');
            $table->index('created_by', 'activities_created_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
