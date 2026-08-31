<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('social_worker_id')->constrained('social_workers')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['social_worker_id', 'name'], 'attendance_sheets_social_worker_name_unique');
            $table->index('created_at', 'attendance_sheets_created_at_index');
        });

        Schema::create('attendance_sheet_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_sheet_id')->constrained('attendance_sheets')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('person_name');
            $table->string('person_code', 64)->nullable();
            $table->string('national_id', 32)->nullable();
            $table->foreignId('qr_identity_id')->nullable()->constrained('qr_identities')->nullOnDelete();
            $table->string('check_in_method', 32)->default('qr');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('check_out_method', 32)->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_sheet_id', 'person_id'], 'attendance_sheet_entries_sheet_person_unique');
            $table->index(['attendance_sheet_id', 'checked_in_at'], 'attendance_sheet_entries_sheet_checked_in_index');
            $table->index(['attendance_sheet_id', 'checked_out_at'], 'attendance_sheet_entries_sheet_checked_out_index');
            $table->index(['person_id', 'checked_in_at'], 'attendance_sheet_entries_person_checked_in_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sheet_entries');
        Schema::dropIfExists('attendance_sheets');
    }
};
