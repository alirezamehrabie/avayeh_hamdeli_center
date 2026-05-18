<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('social_worker_id')
            ->update([
                'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
                'is_admin' => false,
                'permissions' => json_encode([]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('social_worker_id')
            ->where('access_level', User::ACCESS_LEVEL_SOCIAL_WORKER)
            ->update([
                'access_level' => User::ACCESS_LEVEL_REGULAR,
            ]);
    }
};
