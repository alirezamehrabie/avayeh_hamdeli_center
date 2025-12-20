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
        Schema::create('academic_levels', function (Blueprint $blueprint) {
            $blueprint->tinyIncrements('id'); // Primary Key (1-byte)
            $blueprint->string('title');      // عنوان مقطع
            $blueprint->unsignedTinyInteger('sort_order')->default(0); // برای ترتیب نمایش
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
