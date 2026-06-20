<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const PRIMARY_ADMIN_USERNAME = 'admin';
    public const PRIMARY_ADMIN_FIRSTNAME = 'مهدی';
    public const PRIMARY_ADMIN_LASTNAME = 'نمازی';
    public const PRIMARY_ADMIN_EMAIL = 'admin@local.system';
    public const ACCESS_LEVEL_MANAGER = 'manager';
    public const ACCESS_LEVEL_ADMIN = 'admin';
    public const ACCESS_LEVEL_REGULAR = 'regular_user';
    public const ACCESS_LEVEL_SOCIAL_WORKER = 'social_worker';
    public const ACCESS_LEVEL_DISTRIBUTION_OPERATOR = 'distribution_operator';
    public const ACCESS_LEVEL_CHILD_SUPPORTER = 'child_supporter';
    public const PERMISSION_PEOPLE_REGISTER = 'people_register';
    public const PERMISSION_PEOPLE_EDIT = 'people_edit';
    public const PERMISSION_PEOPLE_DELETE = 'people_delete';
    public const PERMISSION_FULL_ACCESS = 'full_access';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'profile_photo_path',
        'mobile',
        'is_admin',
        'access_level',
        'permissions',
        'social_worker_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'permissions' => 'array',
            'social_worker_id' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        return [
            self::PERMISSION_PEOPLE_REGISTER => 'ثبت مددجو (سریع، کامل)',
            self::PERMISSION_PEOPLE_EDIT => 'ویرایش مددجو (سریع، کامل)',
            self::PERMISSION_PEOPLE_DELETE => 'حذف مددجو (انتقال به بلاک‌لیست)',
            self::PERMISSION_FULL_ACCESS => 'دسترسی کامل',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $permissions
     */
    public static function createRoleAccount(array $attributes, array $permissions = []): self
    {
        $username = mb_strtolower(trim((string) $attributes['name']));
        $accessLevel = (string) ($attributes['access_level'] ?? self::ACCESS_LEVEL_REGULAR);

        return self::create(array_merge(
            [
                'name' => $username,
                'first_name' => trim((string) ($attributes['first_name'] ?? '')),
                'last_name' => trim((string) ($attributes['last_name'] ?? '')),
                'email' => $attributes['email'] ?? $username . '@local.system',
                'password' => $attributes['password'],
                'access_level' => $accessLevel,
                'is_admin' => in_array($accessLevel, [self::ACCESS_LEVEL_MANAGER, self::ACCESS_LEVEL_ADMIN], true),
                'permissions' => self::normalizePermissionKeys($permissions),
            ],
            Arr::except($attributes, [
                'name',
                'first_name',
                'last_name',
                'email',
                'password',
                'access_level',
                'is_admin',
                'permissions',
            ])
        ));
    }

    /**
     * @param  array{
     *     mobile: string,
     *     first_name: string,
     *     last_name: string,
     *     monthly_donation_amount: int,
     *     child_preferences?: ?string,
     *     monthly_payment_reminder_methods: list<string>,
     *     is_social_media_active: bool,
     *     created_by?: ?int
     * }  $attributes
     */
    public static function registerSponsorAccount(array $attributes): self
    {
        return DB::transaction(function () use ($attributes): self {
            $mobile = preg_replace('/\D+/', '', (string) $attributes['mobile']) ?: (string) $attributes['mobile'];
            $firstName = trim((string) $attributes['first_name']);
            $lastName = trim((string) $attributes['last_name']);

            $query = self::query()->where('name', $mobile);

            if (Schema::hasColumn('users', 'mobile')) {
                $query->orWhere('mobile', $mobile);
            }

            $user = $query->first();

            if ($user) {
                if (! in_array($user->access_level, [self::ACCESS_LEVEL_REGULAR, self::ACCESS_LEVEL_CHILD_SUPPORTER], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'mobile' => 'This mobile number belongs to another role-based user account.',
                    ]);
                }

                $user->update([
                    'name' => $mobile,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $mobile . '@local.system',
                    'access_level' => self::ACCESS_LEVEL_CHILD_SUPPORTER,
                    'is_admin' => false,
                    'permissions' => [],
                    'mobile' => $mobile,
                ]);
            } else {
                $user = self::createRoleAccount([
                    'name' => $mobile,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $mobile . '@local.system',
                    'password' => Str::random(32),
                    'access_level' => self::ACCESS_LEVEL_CHILD_SUPPORTER,
                    'mobile' => $mobile,
                ]);
            }

            $profile = $user->sponsorProfile;

            $profileData = [
                'monthly_donation_amount' => (int) $attributes['monthly_donation_amount'],
                'child_preferences' => $attributes['child_preferences'] ?? null,
                'monthly_payment_reminder_methods' => array_values($attributes['monthly_payment_reminder_methods']),
                'is_social_media_active' => (bool) $attributes['is_social_media_active'],
                'created_by' => $attributes['created_by'] ?? null,
            ];

            if ($profile) {
                $profile->update($profileData);
            } else {
                $profileData['supporter_code'] = SponsorProfile::generateNextSupporterCode();
                $user->sponsorProfile()->create($profileData);
            }

            return $user->refresh();
        });
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function normalizePermissionKeys(array $permissions): array
    {
        $permissions = array_values(array_unique(array_intersect(
            $permissions,
            array_keys(self::permissionOptions())
        )));

        if (in_array(self::PERMISSION_FULL_ACCESS, $permissions, true)) {
            return [self::PERMISSION_FULL_ACCESS];
        }

        return $permissions;
    }

    public function isAdmin(): bool
    {
        if (! Schema::hasColumn($this->getTable(), 'access_level')) {
            return (bool) $this->is_admin;
        }

        return in_array($this->access_level, [self::ACCESS_LEVEL_MANAGER, self::ACCESS_LEVEL_ADMIN], true);
    }

    public function isManager(): bool
    {
        if (! Schema::hasColumn($this->getTable(), 'access_level')) {
            return false;
        }

        return $this->access_level === self::ACCESS_LEVEL_MANAGER;
    }

    public function isPrimaryAdmin(): bool
    {
        return $this->name === self::PRIMARY_ADMIN_USERNAME || $this->email === self::PRIMARY_ADMIN_EMAIL;
    }

    public function isProtectedManagerAccount(): bool
    {
        return $this->isPrimaryAdmin() && $this->isManager();
    }

    /**
     * @return list<string>
     */
    public function getPermissionKeys(): array
    {
        if (! Schema::hasColumn($this->getTable(), 'permissions')) {
            return [];
        }

        $permissions = $this->permissions;

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_intersect($permissions, array_keys(self::permissionOptions())));
    }

    public function hasLegacyAdminAccess(): bool
    {
        return $this->isAdmin() && $this->getPermissionKeys() === [];
    }

    public function hasFullAccess(): bool
    {
        if ($this->isManager() || $this->hasLegacyAdminAccess()) {
            return true;
        }

        return in_array(self::PERMISSION_FULL_ACCESS, $this->getPermissionKeys(), true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        return in_array($permission, $this->getPermissionKeys(), true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        return array_intersect($permissions, $this->getPermissionKeys()) !== [];
    }

    public function canManagePeople(): bool
    {
        return $this->hasAnyPermission([
            self::PERMISSION_PEOPLE_REGISTER,
            self::PERMISSION_PEOPLE_EDIT,
            self::PERMISSION_PEOPLE_DELETE,
        ]);
    }

    public function canManageSocialWorkers(): bool
    {
        return $this->hasFullAccess();
    }

    public function canAccessSocialWorkerPanel(): bool
    {
        return ! $this->isAdmin()
            && $this->access_level === self::ACCESS_LEVEL_SOCIAL_WORKER
            && $this->socialWorker()->exists();
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->canManagePeople() || $this->canManageSocialWorkers() || $this->isAdmin();
    }

    public function canAccessDistributionOperatorPanel(): bool
    {
        return ! $this->isAdmin()
            && $this->access_level === self::ACCESS_LEVEL_DISTRIBUTION_OPERATOR;
    }

    public function canAccessChildSupporterPanel(): bool
    {
        return ! $this->isAdmin()
            && $this->access_level === self::ACCESS_LEVEL_CHILD_SUPPORTER;
    }

    public function canAccessDistributionOperatorService(Service $service): bool
    {
        return $this->canAccessDistributionOperatorPanel()
            && (int) $service->created_by === (int) $this->id;
    }

    public function getPanelRedirectPath(): string
    {
        if ($this->canAccessChildSupporterPanel()) {
            return route('child-supporter.dashboard');
        }

        if ($this->canAccessAdminPanel()) {
            return route('admin.dashboard');
        }

        if ($this->canAccessSocialWorkerPanel()) {
            return route('social-worker.dashboard');
        }

        if ($this->canAccessDistributionOperatorPanel()) {
            return route('distribution-operator.define-service');
        }

        return '/';
    }

    public function socialWorker()
    {
        return $this->belongsTo(SocialWorker::class);
    }

    public function sponsorProfile(): HasOne
    {
        return $this->hasOne(SponsorProfile::class);
    }

    /**
     * @return list<string>
     */
    public function getPermissionLabelsAttribute(): array
    {
        $options = self::permissionOptions();

        return array_values(array_map(
            fn (string $permission) => $options[$permission] ?? $permission,
            $this->getPermissionKeys()
        ));
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }
}
