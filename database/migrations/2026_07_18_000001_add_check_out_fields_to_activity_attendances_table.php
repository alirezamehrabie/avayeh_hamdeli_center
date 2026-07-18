<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_attendances', function (Blueprint $table): void {
            $table->string('check_out_method', 32)->nullable()->after('checked_out_at');
            $table->foreignId('checked_out_by')->nullable()->after('check_out_method')->constrained('users')->nullOnDelete();
            $table->index(['activity_id', 'checked_out_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_attendances', function (Blueprint $table): void {
            $table->dropIndex(['activity_id', 'checked_out_at']);
            $table->dropConstrainedForeignId('checked_out_by');
            $table->dropColumn('check_out_method');
        });
    }
};
