<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the normalized/compact search columns of the people table so household
 * (guardian) search can be as typo-tolerant as List People search.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            if (! Schema::hasColumn('guardians', 'normalized_first_name')) {
                $table->string('normalized_first_name')->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('guardians', 'normalized_last_name')) {
                $table->string('normalized_last_name')->nullable()->after('normalized_first_name');
            }

            if (! Schema::hasColumn('guardians', 'normalized_full_name')) {
                $table->string('normalized_full_name', 511)->nullable()->after('normalized_last_name');
            }

            if (! Schema::hasColumn('guardians', 'compact_first_name')) {
                $table->string('compact_first_name')->nullable()->after('normalized_full_name');
            }

            if (! Schema::hasColumn('guardians', 'compact_last_name')) {
                $table->string('compact_last_name')->nullable()->after('compact_first_name');
            }

            if (! Schema::hasColumn('guardians', 'compact_full_name')) {
                $table->string('compact_full_name', 511)->nullable()->after('compact_last_name');
            }
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->index('normalized_first_name', 'idx_guardians_normalized_first_name');
            $table->index('normalized_last_name', 'idx_guardians_normalized_last_name');
            $table->index('normalized_full_name', 'idx_guardians_normalized_full_name');
            $table->index('compact_first_name', 'idx_guardians_compact_first_name');
            $table->index('compact_last_name', 'idx_guardians_compact_last_name');
            $table->index('compact_full_name', 'idx_guardians_compact_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropIndex('idx_guardians_normalized_first_name');
            $table->dropIndex('idx_guardians_normalized_last_name');
            $table->dropIndex('idx_guardians_normalized_full_name');
            $table->dropIndex('idx_guardians_compact_first_name');
            $table->dropIndex('idx_guardians_compact_last_name');
            $table->dropIndex('idx_guardians_compact_full_name');
            $table->dropColumn([
                'normalized_first_name',
                'normalized_last_name',
                'normalized_full_name',
                'compact_first_name',
                'compact_last_name',
                'compact_full_name',
            ]);
        });
    }
};
