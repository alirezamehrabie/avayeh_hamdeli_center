<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasTable('service_categories')) {
            return;
        }

        $this->ensureServiceColumns();
        $this->ensureServiceCategoryColumns();
        $this->ensureServiceDeliveryColumns();
        $this->dropLegacyCategoryConstraints();
        $this->ensureCategoryIndexes();
        $this->backfillServiceCodesAndNames();
        $this->bindLegacyCategoriesToServices();
        $this->syncServiceTotalsFromCategories();
        $this->ensureServiceUniqueIndexes();
    }

    public function down(): void
    {
        if (Schema::hasTable('service_categories') && Schema::hasColumn('service_categories', 'service_id')) {
            DB::table('service_categories')->whereNotNull('service_id')->delete();
        }

        if (Schema::hasTable('service_deliveries')) {
            $this->dropForeignKeyByColumn('service_deliveries', 'service_category_id');
            $this->dropColumnSafely('service_deliveries', 'service_category_id');
        }

        if (Schema::hasTable('service_categories')) {
            $this->dropIndexIfExists('service_categories', 'service_categories_service_id_sort_id_index');
            $this->dropIndexIfExists('service_categories', 'service_categories_code_unique');
            $this->dropIndexIfExists('service_categories', 'service_categories_service_id_name_unique');
            $this->dropForeignKeyByColumn('service_categories', 'service_id');
            $this->dropColumnSafely('service_categories', 'service_id');
            $this->dropColumnSafely('service_categories', 'code');
            $this->dropColumnSafely('service_categories', 'quantity');
            $this->dropColumnSafely('service_categories', 'unit');
            $this->dropColumnSafely('service_categories', 'value');
            $this->dropColumnSafely('service_categories', 'deleted_at');

            if (Schema::hasColumn('service_categories', 'service_name_id') && ! $this->hasIndex('service_categories', 'service_categories_service_name_id_name_unique')) {
                Schema::table('service_categories', function (Blueprint $table): void {
                    $table->unique(['service_name_id', 'name']);
                });
            }
        }

        if (Schema::hasTable('services')) {
            $this->dropIndexIfExists('services', 'services_code_unique');
            $this->dropIndexIfExists('services', 'services_name_unique');
            $this->dropColumnSafely('services', 'total_financial_value');
            $this->dropColumnSafely('services', 'name');
            $this->dropColumnSafely('services', 'code');
        }
    }

    protected function ensureServiceColumns(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'code')) {
                $table->string('code')->nullable()->after('id');
            }

            if (! Schema::hasColumn('services', 'name')) {
                $table->string('name')->nullable()->after('code');
            }

            if (! Schema::hasColumn('services', 'total_financial_value')) {
                $table->unsignedBigInteger('total_financial_value')->default(0)->after('total_service_value');
            }
        });
    }

    protected function ensureServiceCategoryColumns(): void
    {
        Schema::table('service_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_categories', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('id')->constrained('services')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('service_categories', 'code')) {
                $table->string('code')->nullable()->after('service_id');
            }

            if (! Schema::hasColumn('service_categories', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0)->after('name');
            }

            if (! Schema::hasColumn('service_categories', 'unit')) {
                $table->string('unit')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('service_categories', 'value')) {
                $table->unsignedBigInteger('value')->default(0)->after('unit');
            }

            if (! Schema::hasColumn('service_categories', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    protected function ensureServiceDeliveryColumns(): void
    {
        if (! Schema::hasTable('service_deliveries')) {
            return;
        }

        if (! Schema::hasColumn('service_deliveries', 'service_category_id')) {
            Schema::table('service_deliveries', function (Blueprint $table): void {
                $table->foreignId('service_category_id')
                    ->nullable()
                    ->after('service_id')
                    ->constrained('service_categories')
                    ->nullOnDelete();
            });
        }
    }

    protected function dropLegacyCategoryConstraints(): void
    {
        $this->dropIndexIfExists('service_categories', 'service_categories_service_name_id_name_unique');
        $this->dropIndexIfExists('service_categories', 'service_categories_code_unique');
        $this->dropIndexIfExists('service_categories', 'service_categories_service_id_name_unique');
    }

    protected function ensureCategoryIndexes(): void
    {
        if (! $this->hasIndex('service_categories', 'service_categories_service_id_sort_id_index')) {
            Schema::table('service_categories', function (Blueprint $table): void {
                $table->index(['service_id', 'sort_id'], 'service_categories_service_id_sort_id_index');
            });
        }
    }

    protected function backfillServiceCodesAndNames(): void
    {
        if (Schema::hasColumn('services', 'service_code')) {
            DB::table('services')
                ->where(function ($query): void {
                    $query->whereNull('code')->orWhere('code', '');
                })
                ->whereNotNull('service_code')
                ->update([
                    'code' => DB::raw('service_code'),
                ]);
        }

        DB::table('services')
            ->orderBy('id')
            ->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $code = trim((string) ($service->code ?? ''));
                    $name = trim((string) ($service->name ?? ''));

                    if ($code === '') {
                        $code = 'SN-' . str_pad((string) $service->id, 5, '0', STR_PAD_LEFT);
                    }

                    if ($name === '') {
                        $name = $code;
                    }

                    DB::table('services')
                        ->where('id', $service->id)
                        ->update([
                            'code' => $code,
                            'name' => $name,
                            'total_financial_value' => (int) ($service->total_financial_value ?? $service->total_service_value ?? 0),
                        ]);
                }
            });
    }

    protected function bindLegacyCategoriesToServices(): void
    {
        $hasLegacyCategoryLink = Schema::hasColumn('services', 'service_category_id');
        $hasLegacyServiceUnit = Schema::hasColumn('services', 'service_unit');
        $hasServiceDeliveries = Schema::hasTable('service_deliveries') && Schema::hasColumn('service_deliveries', 'service_category_id');

        DB::table('services')
            ->orderBy('id')
            ->chunkById(100, function ($services) use ($hasLegacyCategoryLink, $hasLegacyServiceUnit, $hasServiceDeliveries): void {
                foreach ($services as $service) {
                    $legacyCategory = null;

                    if ($hasLegacyCategoryLink && ! empty($service->service_category_id)) {
                        $legacyCategory = DB::table('service_categories')
                            ->where('id', $service->service_category_id)
                            ->first();
                    }

                    $boundCategory = DB::table('service_categories')
                        ->where('service_id', $service->id)
                        ->orderBy('sort_id')
                        ->orderBy('id')
                        ->first();

                    $deliveredQuantity = Schema::hasTable('service_deliveries')
                        ? (float) DB::table('service_deliveries')->where('service_id', $service->id)->sum('delivered_quantity')
                        : 0.0;

                    if (! $boundCategory) {
                        $baseQuantity = max(0, (float) ($service->total_quantity ?? 0) - $deliveredQuantity);
                        $serviceCode = trim((string) ($service->code ?? ''));
                        $categoryName = trim((string) ($legacyCategory->name ?? ''));

                        if ($categoryName === '') {
                            $categoryName = trim((string) ($service->name ?? ''));
                        }

                        if ($categoryName === '') {
                            $categoryName = $serviceCode !== '' ? $serviceCode : 'Service Category';
                        }

                        $boundCategoryId = DB::table('service_categories')->insertGetId([
                            'service_name_id' => $service->service_name_id ?? $legacyCategory->service_name_id ?? null,
                            'service_id' => $service->id,
                            'code' => $serviceCode !== '' ? $serviceCode . '-CAT001' : null,
                            'name' => $categoryName,
                            'quantity' => $baseQuantity,
                            'unit' => $hasLegacyServiceUnit ? ($service->service_unit ?: null) : null,
                            'value' => (int) ($service->value_per_unit ?? 0),
                            'sort_id' => $legacyCategory->sort_id ?? 1,
                            'created_by' => $legacyCategory->created_by ?? $service->created_by ?? null,
                            'created_at' => $legacyCategory->created_at ?? $service->created_at ?? now(),
                            'updated_at' => now(),
                            'deleted_at' => null,
                        ]);
                    } else {
                        $boundCategoryId = (int) $boundCategory->id;
                    }

                    if ($hasServiceDeliveries) {
                        DB::table('service_deliveries')
                            ->where('service_id', $service->id)
                            ->whereNull('service_category_id')
                            ->update([
                                'service_category_id' => $boundCategoryId,
                            ]);
                    }
                }
            });
    }

    protected function syncServiceTotalsFromCategories(): void
    {
        DB::table('services')
            ->orderBy('id')
            ->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $categoryTotals = DB::table('service_categories')
                        ->where('service_id', $service->id)
                        ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity')
                        ->selectRaw('COALESCE(SUM(quantity * value), 0) as total_financial_value')
                        ->first();

                    DB::table('services')
                        ->where('id', $service->id)
                        ->update([
                            'total_quantity' => (float) ($categoryTotals->total_quantity ?? 0),
                            'total_service_value' => (int) ($categoryTotals->total_financial_value ?? 0),
                            'total_financial_value' => (int) ($categoryTotals->total_financial_value ?? 0),
                        ]);
                }
            });
    }

    protected function ensureServiceUniqueIndexes(): void
    {
        if (! $this->hasIndex('services', 'services_code_unique')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->unique('code');
            });
        }

        if (! $this->hasIndex('services', 'services_name_unique')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->unique('name');
            });
        }
    }

    protected function dropColumnSafely(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $database = DB::getDatabaseName();

        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('CONSTRAINT_NAME')
            ->where('CONSTRAINT_NAME', '!=', 'PRIMARY')
            ->pluck('CONSTRAINT_NAME')
            ->all();

        foreach ($foreignKeys as $foreignKey) {
            try {
                DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $foreignKey . '`');
            } catch (\Throwable) {
                // Ignore already-dropped constraints.
            }
        }

        try {
            DB::statement('ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`');
        } catch (\Throwable) {
            // Ignore if another migration already removed the column.
        }
    }

    protected function dropForeignKeyByColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

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
