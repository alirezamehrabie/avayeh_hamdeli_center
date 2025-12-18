<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('family_statuses', 'living_parents')) {
                $table->dropColumn('living_parents');
            }

            if (Schema::hasColumn('family_statuses', 'death_year')) {
                $table->dropColumn('death_year');
            }

            if (Schema::hasColumn('family_statuses', 'death_reason')) {
                $table->dropColumn('death_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            $table->string('living_parents')->nullable();
            $table->integer('death_year')->nullable();
            $table->string('death_reason')->nullable();
        });
    }
};
