<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('access_level')->default(User::ACCESS_LEVEL_REGULAR)->after('is_admin');
        });

        DB::table('users')->where('is_admin', true)->update([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
        ]);

        DB::table('users')
            ->where('name', User::PRIMARY_ADMIN_USERNAME)
            ->where('email', User::PRIMARY_ADMIN_EMAIL)
            ->update([
                'access_level' => User::ACCESS_LEVEL_MANAGER,
                'is_admin' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
};
