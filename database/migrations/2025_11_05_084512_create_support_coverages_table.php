<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->string('organization_type')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('support_card_image')->nullable();
            $table->date('coverage_start_date')->nullable();
            $table->boolean('active_status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_coverages');
    }
};
