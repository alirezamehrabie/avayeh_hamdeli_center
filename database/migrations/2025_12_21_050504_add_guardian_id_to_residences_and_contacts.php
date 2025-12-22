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
        Schema::table('residences', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->onDelete('cascade');
            $table->foreignId('person_id')->nullable(); // اجازه می‌دهیم person_id نال باشد
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->onDelete('cascade');
            $table->foreignId('person_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residences_and_contacts', function (Blueprint $table) {
            //
        });
    }
};
