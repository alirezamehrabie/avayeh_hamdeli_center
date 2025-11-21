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
            $table->unsignedBigInteger('disability_type_id')->nullable()->after('has_disability');
            $table->foreign('disability_type_id')->references('id')->on('disability_types')->nullOnDelete();
            $table->dropColumn('disability_type'); // رشته قبلی حذف می‌شود
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
