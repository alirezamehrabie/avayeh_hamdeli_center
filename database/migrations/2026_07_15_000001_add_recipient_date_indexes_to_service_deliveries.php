<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->index(['person_id', 'delivered_at'], 'sd_person_date_idx');
            $table->index(['guardian_id', 'delivered_at'], 'sd_guardian_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->dropIndex('sd_person_date_idx');
            $table->dropIndex('sd_guardian_date_idx');
        });
    }
};
