<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'google_id',
        'avatar',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    protected ?array $cachedPermissions = null;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function approvals()
    {
        return $this->hasMany(EventApproval::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === Role::SUPER_ADMIN;
    }

    public function isEventManager(): bool
    {
        return $this->role?->name === Role::EVENT_MANAGER;
    }

    public function isTheaterManager(): bool
    {
        return $this->role?->name === Role::THEATER_MANAGER;
    }

    public function isReceptionist(): bool
    {
        return $this->role?->name === Role::RECEPTIONIST;
    }

    public function isUniversityOffice(): bool
    {
        return $this->role?->name === Role::UNIVERSITY_OFFICE;
    }

    public function isAdmin(): bool
    {
        $roleName = $this->role?->name;
        return $roleName !== null && $roleName !== Role::USER;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasPermission(string $permissionName): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role.permissions');
        } elseif ($this->role && !$this->role->relationLoaded('permissions')) {
            $this->role->load('permissions');
        }

        if (!$this->role) {
            return false;
        }

        if ($this->role->name === Role::SUPER_ADMIN) {
            return true;
        }

        if ($this->cachedPermissions === null) {
            $this->cachedPermissions = $this->role->permissions
                ->pluck('name')
                ->all();
        }

        return in_array($permissionName, $this->cachedPermissions, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->hasPermission($perm)) {
                return false;
            }
        }
        return true;
    }
    public function deviceTokens()
{
    return $this->hasMany(DeviceToken::class);
}

    public function clearPermissionsCache(): void
    {
        $this->cachedPermissions = null;
    }
}
