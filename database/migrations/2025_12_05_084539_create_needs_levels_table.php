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

            // ۱. فیلد مرجع به مددجو
            $table->foreignId('person_id')
                ->constrained('people')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // ۲. فیلد مرجع به سطح نیاز (بدون ->after())
            $table->foreignId('need_level_id')
                ->nullable()
                ->constrained('need_level_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // بقیه ستون‌ها
            $table->date('evaluation_date')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_levels');
    }
};
