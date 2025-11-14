<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->onDelete('cascade');
            $table->boolean('has_own_account')->default(false)->nullable();
            $table->string('account_holder_relation')->nullable();
            $table->string('card_number', 16)->nullable();
            $table->string('sheba_number', 26)->nullable(); // IR + 24 digits
            $table->string('subsidy_card_number', 16)->nullable();
            $table->string('subsidy_sheba_number', 26)->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_infos');
    }
};
