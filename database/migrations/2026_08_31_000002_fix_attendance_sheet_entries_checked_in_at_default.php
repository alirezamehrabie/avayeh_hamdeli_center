<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL promotes the first non-nullable TIMESTAMP column of a table to
     * DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, which silently
     * rewrote attendance_sheet_entries.checked_in_at every time the check-out
     * columns were saved. Make the column an ordinary nullable timestamp so the
     * recorded entry time stays exactly as it was scanned.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sheet_entries')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `attendance_sheet_entries` MODIFY `checked_in_at` TIMESTAMP NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // The previous ON UPDATE CURRENT_TIMESTAMP behaviour was a defect; do not restore it.
    }
};
