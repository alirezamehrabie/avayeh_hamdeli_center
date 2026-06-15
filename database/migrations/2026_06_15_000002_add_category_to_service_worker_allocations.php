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
            if (! Schema::hasColumn('service_social_worker', 'service_category_id')) {
                $table->foreignId('service_category_id')->nullable()->after('service_id');
            }
        });

        DB::table('service_social_worker')
            ->whereNull('service_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($allocations): void {
                foreach ($allocations as $allocation) {
                    $categoryId = DB::table('service_categories')
                        ->where('service_id', $allocation->service_id)
                        ->whereNull('deleted_at')
                        ->orderBy('sort_id')
                        ->orderBy('id')
                        ->value('id');

                    if (! $categoryId) {
                        continue;
                    }

                    DB::table('service_social_worker')
                        ->where('id', $allocation->id)
                        ->update(['service_category_id' => $categoryId]);
                }
            });

        if (DB::table('service_social_worker')->whereNull('service_category_id')->exists()) {
            throw new RuntimeException('Cannot require service_category_id while worker allocations without categories still exist.');
        }

        $this->dropForeignKeyByColumn('service_social_worker', 'service_category_id');

        if (! $this->hasIndex('service_social_worker', 'ssw_service_worker_category_unique')) {
            Schema::table('service_social_worker', function (Blueprint $table): void {
                $table->unique(
                    ['service_id', 'social_worker_id', 'service_category_id'],
                    'ssw_service_worker_category_unique'
                );
            });
        }

        $this->dropIndexIfExists('service_social_worker', 'service_social_worker_service_id_social_worker_id_unique');

        DB::statement('ALTER TABLE service_social_worker MODIFY service_category_id BIGINT UNSIGNED NOT NULL');

        Schema::table('service_social_worker', function (Blueprint $table): void {
            $table->foreign('service_category_id')
                ->references('id')
                ->on('service_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_social_worker') || ! Schema::hasColumn('service_social_worker', 'service_category_id')) {
            return;
        }

        $this->dropForeignKeyByColumn('service_social_worker', 'service_category_id');

        if (! $this->hasIndex('service_social_worker', 'ssw_service_id_index')) {
            Schema::table('service_social_worker', function (Blueprint $table): void {
                $table->index('service_id', 'ssw_service_id_index');
            });
        }

        $this->dropIndexIfExists('service_social_worker', 'ssw_service_worker_category_unique');

        Schema::table('service_social_worker', function (Blueprint $table): void {
            $table->dropColumn('service_category_id');
        });

        if (! $this->hasIndex('service_social_worker', 'service_social_worker_service_id_social_worker_id_unique')) {
            Schema::table('service_social_worker', function (Blueprint $table): void {
                $table->unique(['service_id', 'social_worker_id']);
            });
        }
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

    protected function hasIndex(string $table, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->hasIndex($table, $indexName)) {
            return;
        }

        DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $indexName . '`');
    }
};
