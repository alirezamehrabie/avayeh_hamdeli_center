<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationLevelsTable extends Migration
{
    public function up()
    {
        Schema::create('education_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('عنوان سطح تحصیلات به فارسی');
            $table->integer('sort_order')->default(0)->comment('ترتیب نمایش در لیست‌ها');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('education_levels');
    }
}
