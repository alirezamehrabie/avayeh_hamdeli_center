<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('needs_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->string('need_level'); // بحرانی، بالا، متوسط، پایین
            $table->date('evaluation_date');
            $table->string('reviewer_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_levels');
    }
};
