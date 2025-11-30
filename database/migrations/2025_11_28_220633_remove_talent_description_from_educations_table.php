<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * حذف ستون talent_description از جدول educations
     */
    public function up(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            if (Schema::hasColumn('educations', 'talent_description')) {
                $table->dropColumn('talent_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->text('talent_description')->nullable()->after('monthly_income');
        });
    }
};
