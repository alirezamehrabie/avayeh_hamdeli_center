<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_names', 'deleted_at')) {
            Schema::table('service_names', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_names', 'deleted_at')) {
            Schema::table('service_names', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
