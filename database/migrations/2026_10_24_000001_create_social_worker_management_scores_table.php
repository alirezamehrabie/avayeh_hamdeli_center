<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_worker_management_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_worker_id')
                ->constrained('social_workers')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('سال شمسی ارزیابی');
            $table->unsignedTinyInteger('month')->comment('ماه شمسی ارزیابی (۱ تا ۱۲)');
            $table->unsignedTinyInteger('score')->default(10)->comment('ضریب ارزیابی مدیریت (۱ تا ۱۰)');
            $table->text('notes')->nullable()->comment('یادداشت یا علت تغییر ضریب توسط مدیریت');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['social_worker_id', 'year', 'month'], 'sw_mgmt_score_worker_year_month_unique');
            $table->index(['year', 'month'], 'sw_mgmt_score_year_month_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_worker_management_scores');
    }
};
