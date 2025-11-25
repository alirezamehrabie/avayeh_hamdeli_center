<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // عنوان نوع کار (مثلا: رسمی)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_types');
    }
};
