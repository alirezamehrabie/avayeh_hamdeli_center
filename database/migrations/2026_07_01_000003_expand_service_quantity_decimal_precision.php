<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->modifyDecimal('services', 'total_quantity', 'DECIMAL(20,2) NOT NULL');
        $this->modifyDecimal('services', 'quantity_delivered', 'DECIMAL(20,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_categories', 'quantity', 'DECIMAL(20,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_social_worker', 'allocated_quantity', 'DECIMAL(20,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_deliveries', 'delivered_quantity', 'DECIMAL(20,2) NOT NULL');
    }

    public function down(): void
    {
        $this->modifyDecimal('services', 'total_quantity', 'DECIMAL(12,2) NOT NULL');
        $this->modifyDecimal('services', 'quantity_delivered', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_categories', 'quantity', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_social_worker', 'allocated_quantity', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->modifyDecimal('service_deliveries', 'delivered_quantity', 'DECIMAL(12,2) NOT NULL');
    }

    private function modifyDecimal(string $table, string $column, string $definition): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }
};
