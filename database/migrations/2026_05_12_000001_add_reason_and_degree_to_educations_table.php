<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->string('reason_for_not_studying')->nullable()->after('education_level_id');
            $table->foreignId('education_degree')->nullable()->after('reason_for_not_studying')
                ->constrained('academic_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_degree');
            $table->dropColumn('reason_for_not_studying');
        });
    }
};
