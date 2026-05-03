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
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

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
    public function isSuperAdmin(): bool
    {
        return $this->role->name === Role::SUPER_ADMIN;
    }

    public function isEventManager(): bool
    {
        return $this->role->name === Role::EVENT_MANAGER;
    }


    public function isTheaterManager(): bool
    {
        return $this->role->name === Role::THEATER_MANAGER;
    }

    public function isReceptionist(): bool
    {
        return $this->role->name === Role::RECEPTIONIST;
    }

    public function isAdmin(): bool
    {
        return $this->role->name !== Role::USER;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}