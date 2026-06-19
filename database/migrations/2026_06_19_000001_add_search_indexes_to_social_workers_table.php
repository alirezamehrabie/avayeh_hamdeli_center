<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_workers', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'idx_social_workers_active_created_at');
            $table->index('first_name', 'idx_social_workers_first_name');
            $table->index('last_name', 'idx_social_workers_last_name');
            $table->index('mobile', 'idx_social_workers_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('social_workers', function (Blueprint $table) {
            $table->dropIndex('idx_social_workers_active_created_at');
            $table->dropIndex('idx_social_workers_first_name');
            $table->dropIndex('idx_social_workers_last_name');
            $table->dropIndex('idx_social_workers_mobile');
        });
    }
};
