<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_INDEX = 'service_categories_service_name_id_name_unique';
    private const ACTIVE_INDEX = 'sc_service_id_name_active_unique';
    private const ACTIVE_COLUMN = 'active_unique_key';

    public function up(): void
    {
        if (! Schema::hasTable('service_categories')) {
            return;
        }

        Schema::table('service_categories', function (Blueprint $table): void {
            if ($this->hasIndex(self::LEGACY_INDEX)) {
                $table->dropUnique(self::LEGACY_INDEX);
            }

            if (! Schema::hasColumn('service_categories', self::ACTIVE_COLUMN)) {
                $table->boolean(self::ACTIVE_COLUMN)
                    ->nullable()
                    ->storedAs('case when deleted_at is null then 1 else null end');
            }
        });

        Schema::table('service_categories', function (Blueprint $table): void {
            if (! $this->hasIndex(self::ACTIVE_INDEX)) {
                $table->unique(['service_id', 'name', self::ACTIVE_COLUMN], self::ACTIVE_INDEX);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_categories')) {
            return;
        }

        Schema::table('service_categories', function (Blueprint $table): void {
            if ($this->hasIndex(self::ACTIVE_INDEX)) {
                $table->dropUnique(self::ACTIVE_INDEX);
            }

            if (Schema::hasColumn('service_categories', self::ACTIVE_COLUMN)) {
                $table->dropColumn(self::ACTIVE_COLUMN);
            }

            if (! $this->hasIndex(self::LEGACY_INDEX) && Schema::hasColumn('service_categories', 'service_name_id')) {
                $table->unique(['service_name_id', 'name'], self::LEGACY_INDEX);
            }
        });
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            return collect(Schema::getIndexes('service_categories'))
                ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
        } catch (\Throwable) {
            // Fall back for drivers/versions that do not expose Schema::getIndexes().
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'service_categories')
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
