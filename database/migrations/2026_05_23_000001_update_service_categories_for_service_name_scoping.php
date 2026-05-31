<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('service_name_id')->nullable()->after('id')->constrained('service_names')->cascadeOnDelete();
            $table->unsignedInteger('sort_id')->nullable()->after('name');
        });

        DB::table('service_categories')
            ->whereNull('sort_id')
            ->orderBy('id')
            ->get()
            ->each(function ($category, $index) {
                DB::table('service_categories')
                    ->where('id', $category->id)
                    ->update(['sort_id' => $index + 1]);
            });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->index(['service_name_id', 'sort_id']);
            $table->unique(['service_name_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropUnique(['service_name_id', 'name']);
            $table->dropIndex(['service_name_id', 'sort_id']);
            $table->dropConstrainedForeignId('service_name_id');
            $table->dropColumn('sort_id');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
