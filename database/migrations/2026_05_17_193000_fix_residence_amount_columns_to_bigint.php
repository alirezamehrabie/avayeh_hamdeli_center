<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->unsignedBigInteger('deposit_amount')->nullable()->change();
            $table->unsignedBigInteger('monthly_rent')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->unsignedInteger('deposit_amount')->nullable()->change();
            $table->unsignedInteger('monthly_rent')->nullable()->change();
        });
    }
};
