<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_social_worker')) {
            return;
        }

        Schema::table('service_social_worker', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_social_worker', 'assigned_by_user_id')) {
                $table->foreignId('assigned_by_user_id')
                    ->nullable()
                    ->after('allocated_quantity')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'created_by')) {
            return;
        }

        DB::table('service_social_worker')
            ->join('services', 'services.id', '=', 'service_social_worker.service_id')
            ->whereNull('service_social_worker.assigned_by_user_id')
            ->whereNotNull('services.created_by')
            ->update([
                'service_social_worker.assigned_by_user_id' => DB::raw('services.created_by'),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_social_worker') || ! Schema::hasColumn('service_social_worker', 'assigned_by_user_id')) {
            return;
        }

        Schema::table('service_social_worker', function (Blueprint $table): void {
            $table->dropForeign(['assigned_by_user_id']);
            $table->dropColumn('assigned_by_user_id');
        });
    }
};
