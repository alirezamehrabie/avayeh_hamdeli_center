<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTemplatesTable();
        $this->backfillCatalogCategories();
        $this->backfillDeliveryCategories();
        $this->addDeliveryIndex();
    }

    public function down(): void
    {
        $this->dropDeliveryIndex();

        if (Schema::hasTable('service_category_templates')) {
            Schema::dropIfExists('service_category_templates');
        }
    }

    protected function createTemplatesTable(): void
    {
        if (Schema::hasTable('service_category_templates')) {
            return;
        }

        Schema::create('service_category_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_name_id')->constrained('service_names')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['service_name_id', 'name'], 'service_category_templates_service_name_id_name_unique');
            $table->index(['service_name_id', 'sort_id'], 'service_category_templates_service_name_id_sort_id_index');
        });
    }

    protected function backfillCatalogCategories(): void
    {
        if (! Schema::hasTable('service_categories') || ! Schema::hasTable('service_category_templates')) {
            return;
        }

        DB::table('service_categories')
            ->whereNull('service_id')
            ->orderBy('id')
            ->chunkById(100, function ($categories): void {
                $payload = [];

                foreach ($categories as $category) {
                    if (! $category->service_name_id) {
                        continue;
                    }

                    $payload[] = [
                        'service_name_id' => (int) $category->service_name_id,
                        'name' => (string) $category->name,
                        'sort_id' => (int) ($category->sort_id ?? 1),
                        'created_by' => $category->created_by ? (int) $category->created_by : null,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                        'deleted_at' => $category->deleted_at,
                    ];
                }

                if ($payload === []) {
                    return;
                }

                DB::table('service_category_templates')->upsert(
                    $payload,
                    ['service_name_id', 'name'],
                    ['sort_id', 'created_by', 'created_at', 'updated_at', 'deleted_at']
                );
            });

        DB::table('service_categories')->whereNull('service_id')->delete();
    }

    protected function addDeliveryIndex(): void
    {
        if (! Schema::hasTable('service_deliveries')) {
            return;
        }

        $indexName = 'sd_service_date_cat_idx';

        if ($this->hasIndex('service_deliveries', $indexName)) {
            return;
        }

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->index(['service_id', 'delivered_at', 'service_category_id'], 'sd_service_date_cat_idx');
        });
    }

    protected function backfillDeliveryCategories(): void
    {
        if (! Schema::hasTable('service_deliveries') || ! Schema::hasTable('service_categories')) {
            return;
        }

        DB::table('service_deliveries')
            ->whereNull('service_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    $categoryId = DB::table('service_categories')
                        ->where('service_id', $delivery->service_id)
                        ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
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
    }

    protected function dropDeliveryIndex(): void
    {
        if (! $this->hasIndex('service_deliveries', 'sd_service_date_cat_idx')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `service_deliveries` DROP INDEX `sd_service_date_cat_idx`');
        } catch (\Throwable) {
            // Ignore already-dropped index.
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
};
