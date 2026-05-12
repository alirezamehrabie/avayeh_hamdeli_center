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

            // تبدیل ستون متنی وضعیت سکونت به کلید خارجی
            $table->foreignId('residence_status_id')
                ->nullable()
                ->constrained('residence_status_types')
                ->nullOnDelete();

            $table->boolean('is_local_to_city')->nullable();
            $table->unsignedBigInteger('deposit_amount')->nullable();
            $table->unsignedBigInteger('monthly_rent')->nullable();
            $table->integer('residence_duration_years')->nullable();
            $table->text('address')->nullable();

            // تبدیل ستون متنی ناحیه به کلید خارجی
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residences');
    }
};
