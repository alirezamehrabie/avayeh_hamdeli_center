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
            $table->id();
            $table->unsignedInteger('last_code')->default(999); // اولین کد = 1000
            $table->timestamps();
        });

        // اضافه کردن رکورد پایه
        DB::table('guardian_code_counter')->insert([
            'last_code' => 999
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_code_counter');
    }
};
