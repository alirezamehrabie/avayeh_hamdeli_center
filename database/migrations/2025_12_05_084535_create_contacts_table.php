<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('landline_phone')->nullable();
            $table->string('trusted_person_phone', 11)->nullable();
            $table->string('messenger_type')->nullable(); // ایتا، واتس‌اپ
            $table->string('messenger_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
