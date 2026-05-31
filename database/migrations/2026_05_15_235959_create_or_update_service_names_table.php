<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_names')) {
            Schema::create('service_names', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedInteger('sort_id')->default(999)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
            return;
        }

        Schema::table('service_names', function (Blueprint $table) {
            if (! Schema::hasColumn('service_names', 'sort_id')) {
                $table->unsignedInteger('sort_id')->default(999)->after('name')->index();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('service_names') && Schema::hasColumn('service_names', 'sort_id')) {
            Schema::table('service_names', function (Blueprint $table) {
                $table->dropColumn('sort_id');
            });
        }
    }
};
