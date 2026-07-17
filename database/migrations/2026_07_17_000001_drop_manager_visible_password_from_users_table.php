<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'manager_visible_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manager_visible_password');
        });
    }

    public function down(): void
    {
        // Credential recovery is intentionally not restored during rollback.
    }
};
