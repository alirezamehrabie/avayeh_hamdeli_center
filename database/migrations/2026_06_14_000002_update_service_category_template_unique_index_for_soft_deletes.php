<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'service_category_templates_service_name_id_name_unique';
    private const NEW_INDEX = 'sct_service_name_name_active_unique';
    private const ACTIVE_COLUMN = 'active_unique_key';

    public function up(): void
    {
        if (! Schema::hasTable('service_category_templates')) {
            return;
        }

        Schema::table('service_category_templates', function (Blueprint $table): void {
            if ($this->hasIndex(self::OLD_INDEX)) {
                $table->dropUnique(self::OLD_INDEX);
            }

            if (! Schema::hasColumn('service_category_templates', self::ACTIVE_COLUMN)) {
                $table->boolean(self::ACTIVE_COLUMN)
                    ->nullable()
                    ->storedAs('case when deleted_at is null then 1 else null end');
            }
        });

        Schema::table('service_category_templates', function (Blueprint $table): void {
            if (! $this->hasIndex(self::NEW_INDEX)) {
                $table->unique(['service_name_id', 'name', self::ACTIVE_COLUMN], self::NEW_INDEX);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_category_templates')) {
            return;
        }

        Schema::table('service_category_templates', function (Blueprint $table): void {
            if ($this->hasIndex(self::NEW_INDEX)) {
                $table->dropUnique(self::NEW_INDEX);
            }

            if (Schema::hasColumn('service_category_templates', self::ACTIVE_COLUMN)) {
                $table->dropColumn(self::ACTIVE_COLUMN);
            }

            if (! $this->hasIndex(self::OLD_INDEX)) {
                $table->unique(['service_name_id', 'name'], self::OLD_INDEX);
            }
        });
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            return collect(Schema::getIndexes('service_category_templates'))
                ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
        } catch (\Throwable) {
            // Fall back for drivers/versions that do not expose Schema::getIndexes().
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'service_category_templates')
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
