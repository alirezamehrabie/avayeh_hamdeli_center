<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERSIAN_PREFIX = 'متفرقه - ';
    private const LEGACY_PREFIX = 'Misc - ';

    public function up(): void
    {
        $this->renameLegacyServices();
        $this->mergeLegacyServiceNames();

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'status_notes')) {
            DB::table('services')
                ->where('status_notes', 'Ad-hoc service created by distribution operator.')
                ->update([
                    'status_notes' => 'خدمت متفرقه ایجادشده توسط اپراتور توزیع.',
                ]);
        }
    }

    public function down(): void
    {
        $this->renamePersianRows('services');
        $this->renamePersianRows('service_names');

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'status_notes')) {
            DB::table('services')
                ->where('status_notes', 'خدمت متفرقه ایجادشده توسط اپراتور توزیع.')
                ->update([
                    'status_notes' => 'Ad-hoc service created by distribution operator.',
                ]);
        }
    }

    private function renameLegacyServices(): void
    {
        $table = 'services';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'name')) {
            return;
        }

        DB::table($table)
            ->where('name', 'like', self::LEGACY_PREFIX . '%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $sequence = $this->extractSequence((string) $row->name, self::LEGACY_PREFIX);

                    if ($sequence === null) {
                        continue;
                    }

                    DB::table('services')
                        ->where('id', $row->id)
                        ->update([
                            'name' => $this->uniquePersianServiceName($sequence, (int) $row->id),
                        ]);
                }
            });
    }

    private function mergeLegacyServiceNames(): void
    {
        $table = 'service_names';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'name')) {
            return;
        }

        DB::table($table)
            ->where('name', 'like', self::LEGACY_PREFIX . '%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $sequence = $this->extractSequence((string) $row->name, self::LEGACY_PREFIX);

                    if ($sequence === null) {
                        continue;
                    }

                    $targetName = self::PERSIAN_PREFIX . $sequence;
                    $targetId = DB::table($table)
                        ->where('name', $targetName)
                        ->where('id', '!=', $row->id)
                        ->value('id');

                    if ($targetId) {
                        $this->reassignServiceNameReferences((int) $row->id, (int) $targetId);
                        DB::table($table)->where('id', $row->id)->delete();

                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'name' => $targetName,
                        ]);
                }
            });
    }

    private function renamePersianRows(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'name')) {
            return;
        }

        DB::table($table)
            ->where('name', 'like', self::PERSIAN_PREFIX . '%')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $sequence = $this->extractSequence((string) $row->name, self::PERSIAN_PREFIX);

                    if ($sequence === null) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'name' => self::LEGACY_PREFIX . $sequence,
                        ]);
                }
            });
    }

    private function extractSequence(string $name, string $prefix): ?int
    {
        $suffix = trim(substr($name, strlen($prefix)));

        if ($suffix === '' || ! ctype_digit($suffix)) {
            return null;
        }

        return (int) $suffix;
    }

    private function uniquePersianServiceName(int $sequence, int $rowId): string
    {
        $name = self::PERSIAN_PREFIX . $sequence;

        if (! DB::table('services')->where('name', $name)->where('id', '!=', $rowId)->exists()) {
            return $name;
        }

        return $name . ' (' . $rowId . ')';
    }

    private function reassignServiceNameReferences(int $fromId, int $toId): void
    {
        foreach (['services', 'service_categories', 'service_category_templates'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'service_name_id')) {
                continue;
            }

            DB::table($table)
                ->where('service_name_id', $fromId)
                ->update([
                    'service_name_id' => $toId,
                ]);
        }
    }
};
