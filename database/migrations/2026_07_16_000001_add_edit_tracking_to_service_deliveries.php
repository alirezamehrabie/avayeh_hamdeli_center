<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->uuid('delivery_batch_id')->nullable()->after('id')->index();
            $table->foreignId('updated_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->dropIndex(['delivery_batch_id']);
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['delivery_batch_id', 'corrected_at']);
        });
    }
};
