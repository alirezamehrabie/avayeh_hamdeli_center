<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // 1. حذف فیلد رشته‌ای
            $table->dropColumn('sadaat_relation');

            // 2. افزودن کلید خارجی به اطمینان از nullable بودن (اگر نیاز دارید)
            $table->foreignId('sadaat_relation_id')
                ->nullable()
                ->constrained('sadaat_relations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sadaat_relation_id');
            $table->string('sadaat_relation')->nullable()->after('sadaat_status');
        });
    }
};
