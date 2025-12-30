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
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id('id'); // Primary Key (1-byte)
            $table->string('title');      // عنوان مقطع
            $table->unsignedTinyInteger('sort_order')->default(0); // برای ترتیب نمایش
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
