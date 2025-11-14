<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->onDelete('cascade');
            $table->string('residence_status'); // شخصی، اجاره، شراکتی
            $table->boolean('is_local_to_city')->default(true);
            $table->unsignedBigInteger('deposit_amount')->nullable();
            $table->unsignedBigInteger('monthly_rent')->nullable();
            $table->integer('residence_duration_years')->nullable();
            $table->text('address');
            $table->string('district')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residences');
    }
};
