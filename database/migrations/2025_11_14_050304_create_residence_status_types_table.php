<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidenceStatusTypesTable extends Migration
{
    public function up()
    {
        Schema::create('residence_status_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('وضعیت سکونت مانند: شخصی، اجاره، شراکتی');
            $table->integer('sort_order')->default(0)->comment('ترتیب نمایش');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('residence_status_types');
    }
}
