<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            $table->boolean('father_left_home')->default(false)->after('parent_disability_description');
            $table->boolean('mother_left_home')->default(false)->after('father_left_home');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            $table->dropColumn(['father_left_home', 'mother_left_home']);
        });
    }
};
