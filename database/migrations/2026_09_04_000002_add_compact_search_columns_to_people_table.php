<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'compact_first_name')) {
                $table->string('compact_first_name')->nullable()->after('normalized_full_name');
            }

            if (! Schema::hasColumn('people', 'compact_last_name')) {
                $table->string('compact_last_name')->nullable()->after('compact_first_name');
            }

            if (! Schema::hasColumn('people', 'compact_full_name')) {
                $table->string('compact_full_name', 511)->nullable()->after('compact_last_name');
            }
        });

        Schema::table('people', function (Blueprint $table) {
            $table->index('compact_first_name', 'idx_people_compact_first_name');
            $table->index('compact_last_name', 'idx_people_compact_last_name');
            $table->index('compact_full_name', 'idx_people_compact_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_compact_first_name');
            $table->dropIndex('idx_people_compact_last_name');
            $table->dropIndex('idx_people_compact_full_name');
            $table->dropColumn([
                'compact_first_name',
                'compact_last_name',
                'compact_full_name',
            ]);
        });
    }
};
