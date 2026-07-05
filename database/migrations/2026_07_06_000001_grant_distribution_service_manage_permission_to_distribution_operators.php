<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'permissions')) {
            return;
        }

        User::query()
            ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $permissions = User::normalizePermissionKeysForAccessLevel(
                        array_merge($user->getPermissionKeys(), [User::PERMISSION_DISTRIBUTION_SERVICE_MANAGE]),
                        User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR
                    );

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'permissions')) {
            return;
        }

        User::query()
            ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $permissions = array_values(array_diff(
                        $user->getPermissionKeys(),
                        [User::PERMISSION_DISTRIBUTION_SERVICE_MANAGE]
                    ));

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'permissions' => json_encode(
                                User::normalizePermissionKeysForAccessLevel(
                                    $permissions,
                                    User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR
                                ),
                                JSON_UNESCAPED_UNICODE
                            ),
                        ]);
                }
            });
    }
};
