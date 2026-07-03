<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_case_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_type', 32);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('recorded_at')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'recorded_at']);
            $table->index(['person_id', 'record_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_case_records');
    }
};
