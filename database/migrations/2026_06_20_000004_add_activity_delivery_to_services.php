<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table): void {
                if (! Schema::hasColumn('services', 'activity_id')) {
                    $table->foreignId('activity_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('activities')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('services', 'supports_activity_delivery')) {
                    $table->boolean('supports_activity_delivery')->default(false)->after('supports_home_delivery');
                }
            });
        }

        if (! Schema::hasTable('service_deliveries')) {
            return;
        }

        Schema::table('service_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_deliveries', 'activity_attendance_id')) {
                $table->foreignId('activity_attendance_id')
                    ->nullable()
                    ->after('service_category_id')
                    ->constrained('activity_attendances')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('service_deliveries', 'delivery_channel')) {
                $table->string('delivery_channel', 32)->default('home')->after('activity_attendance_id');
            }
        });

        $this->makeSocialWorkerNullable();
        $this->addActivityDeliveryUniqueIndex();
    }

    public function down(): void
    {
        if (Schema::hasTable('service_deliveries')) {
            $this->dropIndexIfExists('service_deliveries', 'sd_attendance_category_unique');

            Schema::table('service_deliveries', function (Blueprint $table): void {
                if (Schema::hasColumn('service_deliveries', 'activity_attendance_id')) {
                    $table->dropConstrainedForeignId('activity_attendance_id');
                }

                if (Schema::hasColumn('service_deliveries', 'delivery_channel')) {
                    $table->dropColumn('delivery_channel');
                }
            });

            $this->makeSocialWorkerRequired();
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table): void {
                if (Schema::hasColumn('services', 'activity_id')) {
                    $table->dropConstrainedForeignId('activity_id');
                }

                if (Schema::hasColumn('services', 'supports_activity_delivery')) {
                    $table->dropColumn('supports_activity_delivery');
                }
            });
        }
    }

    protected function makeSocialWorkerNullable(): void
    {
        if (! Schema::hasColumn('service_deliveries', 'social_worker_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropForeignKeyByColumn('service_deliveries', 'social_worker_id');

        DB::statement('ALTER TABLE service_deliveries MODIFY social_worker_id BIGINT UNSIGNED NULL');

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->foreign('social_worker_id')
                ->references('id')
                ->on('social_workers')
                ->nullOnDelete();
        });
    }

    protected function makeSocialWorkerRequired(): void
    {
        if (! Schema::hasColumn('service_deliveries', 'social_worker_id') || DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::table('service_deliveries')->whereNull('social_worker_id')->exists()) {
            return;
        }

        $this->dropForeignKeyByColumn('service_deliveries', 'social_worker_id');

        DB::statement('ALTER TABLE service_deliveries MODIFY social_worker_id BIGINT UNSIGNED NOT NULL');

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->foreign('social_worker_id')
                ->references('id')
                ->on('social_workers')
                ->cascadeOnDelete();
        });
    }

    protected function addActivityDeliveryUniqueIndex(): void
    {
        if ($this->hasIndex('service_deliveries', 'sd_attendance_category_unique')) {
            return;
        }

        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->unique(['activity_attendance_id', 'service_category_id'], 'sd_attendance_category_unique');
        });
    }

    protected function dropForeignKeyByColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || DB::getDriverName() === 'sqlite') {
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
            DB::statement('ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $foreignKey . '`');
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($index) => ($index->name ?? null) === $indexName);
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

        Schema::table($table, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }
};
