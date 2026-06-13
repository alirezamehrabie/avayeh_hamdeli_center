<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        if (Schema::hasColumn('services', 'service_code')) {
            DB::table('services')
                ->where(function ($query): void {
                    $query->whereNull('code')->orWhere('code', '');
                })
                ->whereNotNull('service_code')
                ->update([
                    'code' => DB::raw('service_code'),
                ]);

            $this->dropIndexIfExists('services', 'services_service_code_unique');
            $this->dropColumnSafely('services', 'service_code');
        }

        $this->dropForeignKeyByColumn('services', 'service_category_id');
        $this->dropColumnSafely('services', 'service_category_id');
        $this->dropColumnSafely('services', 'service_unit');
    }

    public function down(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        if (! Schema::hasColumn('services', 'service_category_id')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->foreignId('service_category_id')
                    ->nullable()
                    ->after('service_name_id')
                    ->constrained('service_categories')
                    ->nullOnDelete();
            });

            DB::table('services')->orderBy('id')->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $categoryId = DB::table('service_categories')
                        ->where('service_id', $service->id)
                        ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                        ->orderBy('sort_id')
                        ->orderBy('id')
                        ->value('id');

                    if ($categoryId) {
                        DB::table('services')
                            ->where('id', $service->id)
                            ->update(['service_category_id' => $categoryId]);
                    }
                }
            });
        }

        if (! Schema::hasColumn('services', 'service_unit')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->string('service_unit')->nullable()->after('total_quantity');
            });

            DB::table('services')->orderBy('id')->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $unit = DB::table('service_categories')
                        ->where('service_id', $service->id)
                        ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                        ->orderBy('sort_id')
                        ->orderBy('id')
                        ->value('unit');

                    if ($unit !== null) {
                        DB::table('services')
                            ->where('id', $service->id)
                            ->update(['service_unit' => $unit]);
                    }
                }
            });
        }

        if (! Schema::hasColumn('services', 'service_code')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->string('service_code')->nullable()->after('name');
            });

            DB::table('services')->update([
                'service_code' => DB::raw('code'),
            ]);

            if (! $this->hasIndex('services', 'services_service_code_unique')) {
                Schema::table('services', function (Blueprint $table): void {
                    $table->unique('service_code');
                });
            }
        }
    }

    protected function dropColumnSafely(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`');
        } catch (\Throwable) {
            // Ignore already-dropped columns.
        }
    }

    protected function dropForeignKeyByColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->all();

        foreach ($foreignKeys as $foreignKey) {
            try {
                DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $foreignKey . '`');
            } catch (\Throwable) {
                // Ignore already-dropped constraints.
            }
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

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

        try {
            DB::statement('ALTER TABLE `' . $table . '` DROP INDEX `' . $indexName . '`');
        } catch (\Throwable) {
            // Ignore already-dropped indexes.
        }
    }
};
