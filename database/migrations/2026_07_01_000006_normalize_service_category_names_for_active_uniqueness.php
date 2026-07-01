<?php

use App\Models\ServiceCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RAW_ACTIVE_INDEX = 'sc_service_id_name_active_unique';
    private const NORMALIZED_ACTIVE_INDEX = 'sc_service_id_normalized_name_active_unique';
    private const ACTIVE_COLUMN = 'active_unique_key';

    public function up(): void
    {
        if (! Schema::hasTable('service_categories')) {
            return;
        }

        Schema::table('service_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_categories', ServiceCategory::NORMALIZED_NAME_COLUMN)) {
                $table->string(ServiceCategory::NORMALIZED_NAME_COLUMN)->nullable()->after('name');
            }
        });

        DB::table('service_categories')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(500, function ($categories): void {
                foreach ($categories as $category) {
                    DB::table('service_categories')
                        ->where('id', $category->id)
                        ->update([
                            ServiceCategory::NORMALIZED_NAME_COLUMN => ServiceCategory::normalizeName((string) $category->name),
                        ]);
                }
            });

        if ($this->hasActiveNormalizedCollisions()) {
            throw new RuntimeException('Cannot enforce normalized service category uniqueness because duplicate active category names already exist after normalization.');
        }

        Schema::table('service_categories', function (Blueprint $table): void {
            if ($this->hasIndex(self::RAW_ACTIVE_INDEX)) {
                $table->dropUnique(self::RAW_ACTIVE_INDEX);
            }
        });

        Schema::table('service_categories', function (Blueprint $table): void {
            if (! $this->hasIndex(self::NORMALIZED_ACTIVE_INDEX)) {
                $table->unique(
                    ['service_id', ServiceCategory::NORMALIZED_NAME_COLUMN, self::ACTIVE_COLUMN],
                    self::NORMALIZED_ACTIVE_INDEX
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_categories')) {
            return;
        }

        Schema::table('service_categories', function (Blueprint $table): void {
            if ($this->hasIndex(self::NORMALIZED_ACTIVE_INDEX)) {
                $table->dropUnique(self::NORMALIZED_ACTIVE_INDEX);
            }

            if (! $this->hasIndex(self::RAW_ACTIVE_INDEX) && Schema::hasColumn('service_categories', 'name')) {
                $table->unique(['service_id', 'name', self::ACTIVE_COLUMN], self::RAW_ACTIVE_INDEX);
            }

            if (Schema::hasColumn('service_categories', ServiceCategory::NORMALIZED_NAME_COLUMN)) {
                $table->dropColumn(ServiceCategory::NORMALIZED_NAME_COLUMN);
            }
        });
    }

    private function hasActiveNormalizedCollisions(): bool
    {
        return DB::table('service_categories')
            ->select('service_id', ServiceCategory::NORMALIZED_NAME_COLUMN)
            ->whereNull('deleted_at')
            ->whereNotNull(ServiceCategory::NORMALIZED_NAME_COLUMN)
            ->groupBy('service_id', ServiceCategory::NORMALIZED_NAME_COLUMN)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
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
