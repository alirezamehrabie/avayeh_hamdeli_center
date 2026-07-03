<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('beneficiary_case_record_attachments')) {
            Schema::create('beneficiary_case_record_attachments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('beneficiary_case_record_id');
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('disk', 32)->default('public');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type', 128)->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('beneficiary_case_record_attachments', function (Blueprint $table): void {
            if (! $this->hasIndex('beneficiary_case_record_attachments', 'case_record_attachment_record_idx')) {
                $table->index('beneficiary_case_record_id', 'case_record_attachment_record_idx');
            }

            if (! $this->hasIndex('beneficiary_case_record_attachments', 'case_record_attachment_uploader_idx')) {
                $table->index('uploaded_by', 'case_record_attachment_uploader_idx');
            }

            if (! $this->hasForeignKey('beneficiary_case_record_attachments', 'case_record_attachment_record_fk')) {
                $table->foreign('beneficiary_case_record_id', 'case_record_attachment_record_fk')
                    ->references('id')
                    ->on('beneficiary_case_records')
                    ->cascadeOnDelete();
            }

            if (! $this->hasForeignKey('beneficiary_case_record_attachments', 'case_record_attachment_uploader_fk')) {
                $table->foreign('uploaded_by', 'case_record_attachment_uploader_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_case_record_attachments');
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM '.$table))
            ->contains(fn ($row): bool => ($row->Key_name ?? null) === $index);
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        return collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $constraint],
        ))->isNotEmpty();
    }
};
