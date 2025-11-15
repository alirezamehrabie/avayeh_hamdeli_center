<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_skill', function (Blueprint $table) {
            $table->foreignId('person_id')
                ->constrained('people')
                ->onDelete('cascade');
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->onDelete('cascade');

            $table->primary(['person_id', 'skill_id']);
            // ایندکس‌های جداگانه (اختیاری برای فیلترینگ دو سویه)
            $table->index('person_id');
            $table->index('skill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_skill');
    }
};
