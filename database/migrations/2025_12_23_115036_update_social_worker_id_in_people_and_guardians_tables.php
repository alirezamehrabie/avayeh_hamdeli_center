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
            $table->dropForeign(['social_worker_id']); // اگر Foreign key تعریف شده
            $table->dropColumn('social_worker_id');
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->foreignId('social_worker_id')->nullable()->constrained('social_workers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            //
        });
    }
};
