<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('need_level_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique()->comment('کد سطح نیاز: A,B,C,D,E');
            $table->string('title')->comment('توضیح فارسی سطح نیاز');
            $table->integer('severity_order')->default(0)->comment('ترتیب شدت از بالا به پایین');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('need_level_types');
    }
};
