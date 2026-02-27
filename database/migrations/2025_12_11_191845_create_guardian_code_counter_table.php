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
        Schema::create('guardian_code_counter', function (Blueprint $table) {
            // کلید Singleton
            $table->unsignedTinyInteger('singleton_id')->primary(); // همیشه = 1

            // مقدار شمارنده
            $table->unsignedInteger('last_code')->default(999); // اولین کد = 1000

            $table->timestamps();
        });

        // درج تنها ردیف مجاز
        DB::table('guardian_code_counter')->insert([
            'singleton_id' => 1,
            'last_code' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_code_counter');
    }
};
