<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'normalized_first_name')) {
                $table->string('normalized_first_name')->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('people', 'normalized_last_name')) {
                $table->string('normalized_last_name')->nullable()->after('normalized_first_name');
            }

            if (! Schema::hasColumn('people', 'normalized_full_name')) {
                $table->string('normalized_full_name', 511)->nullable()->after('normalized_last_name');
            }
        });

        DB::table('people')
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('id')
            ->chunkById(500, function ($people) {
                foreach ($people as $person) {
                    $firstName = $this->normalize((string) $person->first_name);
                    $lastName = $this->normalize((string) $person->last_name);

                    DB::table('people')
                        ->where('id', $person->id)
                        ->update([
                            'normalized_first_name' => $firstName,
                            'normalized_last_name' => $lastName,
                            'normalized_full_name' => trim($firstName . ' ' . $lastName),
                        ]);
                }
            });

        Schema::table('people', function (Blueprint $table) {
            $table->index('normalized_first_name', 'idx_people_normalized_first_name');
            $table->index('normalized_last_name', 'idx_people_normalized_last_name');
            $table->index('normalized_full_name', 'idx_people_normalized_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_normalized_first_name');
            $table->dropIndex('idx_people_normalized_last_name');
            $table->dropIndex('idx_people_normalized_full_name');
            $table->dropColumn([
                'normalized_first_name',
                'normalized_last_name',
                'normalized_full_name',
            ]);
        });
    }

    private function normalize(string $value): string
    {
        $value = str_replace(['ي', 'ى', 'ك', 'ۀ', 'ة'], ['ی', 'ی', 'ک', 'ه', 'ه'], $value);
        $value = str_replace(["\u{200C}", "\u{200D}"], ' ', $value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }
};
