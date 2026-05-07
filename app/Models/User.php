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
    public const PRIMARY_ADMIN_EMAIL = 'admin@local.system';
    public const ACCESS_LEVEL_MANAGER = 'manager';
    public const ACCESS_LEVEL_ADMIN = 'admin';
    public const ACCESS_LEVEL_REGULAR = 'regular_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'is_admin',
        'access_level',

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
}
