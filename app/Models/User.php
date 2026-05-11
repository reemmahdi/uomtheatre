<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * ════════════════════════════════════════════════════════════
 * User Model — UOMTheatre (مُحدّث للمرحلة 1.أ)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات في هذه النسخة (إصلاحات Claude):
 *   - استخدام nullsafe operator (?->) في كل role checks
 *   - cache للصلاحيات لتجنّب مشكلة N+1 query
 *   - تحميل eager للعلاقة role.permissions في hasPermission
 *
 * ════════════════════════════════════════════════════════════
 */
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

    /**
     * ✨ Cache داخلي للصلاحيات (يمنع N+1 query)
     * يُملأ في أول استدعاء لـ hasPermission ويُعاد استخدامه
     */
    protected ?array $cachedPermissions = null;

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
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

    /**
     * العلاقة مع موافقات الفعاليات
     */
    public function approvals()
    {
        return $this->hasMany(EventApproval::class);
    }

    // ════════════════════════════════════════════════════════
    // Role Helpers (مع nullsafe operator)
    // ════════════════════════════════════════════════════════
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

    /**
     * ✨ مُصحَّح: حماية من null role
     * (سابقاً كان يفترض أن role موجود دائماً)
     */
    public function isAdmin(): bool
    {
        $roleName = $this->role?->name;
        return $roleName !== null && $roleName !== Role::USER;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    // ════════════════════════════════════════════════════════
    // ✨ Permission Helpers (مع cache لمنع N+1)
    // ════════════════════════════════════════════════════════

    /**
     * ✨ التحقق ما إذا كان المستخدم يملك صلاحية معيّنة
     *
     * استخدام:
     *   if (Auth::user()->hasPermission('events.create')) { ... }
     *
     * ✨ التحسين: في أول استدعاء يُحمَّل role + permissions معاً (eager)،
     * ثم تُحفَظ في cachedPermissions داخل الـ instance.
     * كل استدعاءات لاحقة = صفر queries.
     */
    public function hasPermission(string $permissionName): bool
    {
        // إذا الدور غير محمّل، نحمّله مع صلاحياته دفعة واحدة
        if (!$this->relationLoaded('role')) {
            $this->load('role.permissions');
        } elseif ($this->role && !$this->role->relationLoaded('permissions')) {
            $this->role->load('permissions');
        }

        if (!$this->role) {
            return false;
        }

        // super_admin له كل الصلاحيات تلقائياً
        if ($this->role->name === Role::SUPER_ADMIN) {
            return true;
        }

        // أول مرة فقط: نملأ الـ cache من collection محمّلة (بدون query إضافي)
        if ($this->cachedPermissions === null) {
            $this->cachedPermissions = $this->role->permissions
                ->pluck('name')
                ->all();
        }

        return in_array($permissionName, $this->cachedPermissions, true);
    }

    /**
     * ✨ التحقق من عدة صلاحيات (يجب امتلاك واحدة على الأقل)
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }

    /**
     * ✨ التحقق من عدة صلاحيات (يجب امتلاك جميعها)
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (!$this->hasPermission($perm)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ✨ مفيد لو احتاجت تنظيف الـ cache يدوياً
     * (مثلاً بعد تحديث الصلاحيات في Permissions screen)
     */
    public function clearPermissionsCache(): void
    {
        $this->cachedPermissions = null;
    }
}
