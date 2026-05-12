<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PRIMARY_ADMIN_USERNAME = 'admin';
    public const PRIMARY_ADMIN_FIRSTNAME = 'مهدی';
    public const PRIMARY_ADMIN_LASTNAME = 'نمازی';
    public const PRIMARY_ADMIN_EMAIL = 'admin@local.system';
    public const ACCESS_LEVEL_MANAGER = 'manager';
    public const ACCESS_LEVEL_ADMIN = 'admin';
    public const ACCESS_LEVEL_REGULAR = 'regular_user';
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
        'is_admin',
        'access_level',
        'permissions',
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

    public function canAccessAdminPanel(): bool
    {
        return $this->canManagePeople() || $this->canManageSocialWorkers() || $this->isAdmin();
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
