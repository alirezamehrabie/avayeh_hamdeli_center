<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ACCESS_LEVEL_CHILD_SUPPORTER = 'child_supporter';

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 11)->nullable()->after('email')->index();
            }
        });

        if (! Schema::hasTable('sponsor_profiles')) {
            Schema::create('sponsor_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('monthly_donation_amount');
                $table->text('child_preferences')->nullable();
                $table->json('monthly_payment_reminder_methods');
                $table->boolean('is_social_media_active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        $this->moveLegacyUserSponsorColumnsToProfiles();

        if (Schema::hasTable('sponsors')) {
            $this->moveLegacySponsorsTableToProfiles();
            Schema::drop('sponsors');
        }

        $this->dropLegacyUserSponsorColumns();
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_profiles');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mobile')) {
                $table->dropColumn('mobile');
            }
        });
    }

    private function moveLegacyUserSponsorColumnsToProfiles(): void
    {
        if (! Schema::hasColumn('users', 'monthly_donation_amount')) {
            return;
        }

        DB::table('users')
            ->where('access_level', self::ACCESS_LEVEL_CHILD_SUPPORTER)
            ->whereNotNull('monthly_donation_amount')
            ->orderBy('id')
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('sponsor_profiles')->updateOrInsert(
                        ['user_id' => $user->id],
                        [
                            'monthly_donation_amount' => $user->monthly_donation_amount,
                            'child_preferences' => $user->child_preferences ?? null,
                            'monthly_payment_reminder_methods' => $user->monthly_payment_reminder_methods ?? json_encode([]),
                            'is_social_media_active' => (bool) ($user->is_social_media_active ?? false),
                            'created_by' => $user->created_by ?? null,
                            'created_at' => $user->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    private function moveLegacySponsorsTableToProfiles(): void
    {
        DB::table('sponsors')->orderBy('id')->chunk(100, function ($sponsors): void {
            foreach ($sponsors as $sponsor) {
                $mobile = preg_replace('/\D+/', '', (string) $sponsor->mobile);

                if ($mobile === '') {
                    continue;
                }

                $existingUser = DB::table('users')
                    ->where('name', $mobile)
                    ->orWhere('mobile', $mobile)
                    ->first();

                if ($existingUser) {
                    $userId = $existingUser->id;

                    DB::table('users')->where('id', $userId)->update([
                        'access_level' => self::ACCESS_LEVEL_CHILD_SUPPORTER,
                        'mobile' => $mobile,
                        'updated_at' => now(),
                    ]);
                } else {
                    [$firstName, $lastName] = $this->splitFullName((string) $sponsor->full_name);

                    $userId = DB::table('users')->insertGetId([
                        'name' => $mobile,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $mobile . '@local.system',
                        'password' => Hash::make(Str::random(32)),
                        'is_admin' => false,
                        'access_level' => self::ACCESS_LEVEL_CHILD_SUPPORTER,
                        'permissions' => json_encode([]),
                        'mobile' => $mobile,
                        'created_at' => $sponsor->created_at ?? now(),
                        'updated_at' => $sponsor->updated_at ?? now(),
                    ]);
                }

                DB::table('sponsor_profiles')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'monthly_donation_amount' => $sponsor->monthly_donation_amount,
                        'child_preferences' => $sponsor->child_preferences,
                        'monthly_payment_reminder_methods' => $sponsor->monthly_payment_reminder_methods ?: json_encode([]),
                        'is_social_media_active' => (bool) $sponsor->is_social_media_active,
                        'created_by' => $sponsor->created_by,
                        'created_at' => $sponsor->created_at ?? now(),
                        'updated_at' => $sponsor->updated_at ?? now(),
                    ]
                );
            }
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $firstName = array_shift($parts) ?: trim($fullName);
        $lastName = trim(implode(' ', $parts));

        return [$firstName, $lastName !== '' ? $lastName : '-'];
    }

    private function dropLegacyUserSponsorColumns(): void
    {
        $columns = [
            'monthly_donation_amount',
            'child_preferences',
            'monthly_payment_reminder_methods',
            'is_social_media_active',
            'created_by',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('users', $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($existingColumns): void {
            if (in_array('created_by', $existingColumns, true)) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable) {
                    //
                }
            }

            $table->dropColumn($existingColumns);
        });
    }
};
