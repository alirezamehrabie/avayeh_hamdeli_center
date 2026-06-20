<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'supports_gate_delivery')) {
                $table->boolean('supports_gate_delivery')->default(true)->after('service_type');
            }

            if (! Schema::hasColumn('services', 'supports_home_delivery')) {
                $table->boolean('supports_home_delivery')->default(true)->after('supports_gate_delivery');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            if (Schema::hasColumn('services', 'supports_home_delivery')) {
                $table->dropColumn('supports_home_delivery');
            }

            if (Schema::hasColumn('services', 'supports_gate_delivery')) {
                $table->dropColumn('supports_gate_delivery');
            }
        });
    }
};
