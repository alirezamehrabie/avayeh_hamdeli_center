<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_deliveries') || ! Schema::hasColumn('service_deliveries', 'service_category_id')) {
            return;
        }

        DB::table('service_deliveries')
            ->whereNull('service_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    $categoryId = DB::table('service_categories')
                        ->where('service_id', $delivery->service_id)
                        ->whereNull('deleted_at')
                        ->orderBy('sort_id')
                        ->orderBy('id')
                        ->value('id');

                    if (! $categoryId) {
                        continue;
                    }

                    DB::table('service_deliveries')
                        ->where('id', $delivery->id)
                        ->update(['service_category_id' => $categoryId]);
                }
            });

        if (DB::table('service_deliveries')->whereNull('service_category_id')->exists()) {
            throw new RuntimeException('Cannot require service_category_id while service deliveries without categories still exist.');
        }

        $this->dropForeignKeyByColumn('service_deliveries', 'service_category_id');

        DB::statement('ALTER TABLE service_deliveries MODIFY service_category_id BIGINT UNSIGNED NOT NULL');

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->foreign('service_category_id')
                ->references('id')
                ->on('service_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_deliveries') || ! Schema::hasColumn('service_deliveries', 'service_category_id')) {
            return;
        }

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->dropForeign(['service_category_id']);
        });

        DB::statement('ALTER TABLE service_deliveries MODIFY service_category_id BIGINT UNSIGNED NULL');

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->foreign('service_category_id')
                ->references('id')
                ->on('service_categories')
                ->nullOnDelete();
        });
    }

    protected function dropForeignKeyByColumn(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->all();

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $foreignKey . '`');
        }
    }
};
