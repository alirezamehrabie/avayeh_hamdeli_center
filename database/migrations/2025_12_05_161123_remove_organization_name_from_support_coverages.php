<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_coverages', function (Blueprint $table) {
            if (Schema::hasColumn('support_coverages', 'organization_name')) {
                $table->dropColumn('organization_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_coverages', function (Blueprint $table) {
            $table->string('organization_name')->nullable()->after('organization_type');
        });
    }
};
