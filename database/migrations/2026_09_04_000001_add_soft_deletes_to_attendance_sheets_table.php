<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sheets', function (Blueprint $table): void {
            // Archiving must not block reusing a sheet name, so the DB-level
            // uniqueness is dropped; validation now scopes uniqueness to
            // non-archived sheets only. The social_worker_id FK needs an
            // index of its own before the composite unique can go away.
            $table->index('social_worker_id', 'attendance_sheets_social_worker_id_index');
            $table->dropUnique('attendance_sheets_social_worker_name_unique');

            $table->foreignId('archived_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('deleted_at', 'attendance_sheets_deleted_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sheets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropIndex('attendance_sheets_deleted_at_index');
            $table->dropColumn('deleted_at');

            $table->unique(['social_worker_id', 'name'], 'attendance_sheets_social_worker_name_unique');
            $table->dropIndex('attendance_sheets_social_worker_id_index');
        });
    }
};
