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
        Schema::table('people', function (Blueprint $table) {
            $table->index('father_national_id', 'idx_people_father_national_id');
            $table->index('mother_national_id', 'idx_people_mother_national_id');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_father_national_id');
            $table->dropIndex('idx_people_mother_national_id');
        });
    }

};
