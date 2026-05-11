<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Role Model — UOMTheatre (مُحدّث للمرحلة 1.أ)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ الجديد في هذه النسخة:
 *   - علاقة many-to-many مع Permission
 *   - دالة hasPermission($name) للتحقق السريع
 *
 * ════════════════════════════════════════════════════════════
 */
class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    // ════════════════════════════════════════════════════════
    // Constants للأدوار
    // ════════════════════════════════════════════════════════
    const SUPER_ADMIN       = 'super_admin';
    const EVENT_MANAGER     = 'event_manager';
    const THEATER_MANAGER   = 'theater_manager';
    const RECEPTIONIST      = 'receptionist';
    const UNIVERSITY_OFFICE = 'university_office';
    const USER              = 'user';

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * ✨ جديد: علاقة many-to-many مع Permission
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    // ════════════════════════════════════════════════════════
    // Helper Methods
    // ════════════════════════════════════════════════════════

    /**
     * ✨ جديد: التحقق ما إذا كان هذا الدور يملك صلاحية معيّنة
     *
     * استخدام:
     *   if ($user->role->hasPermission('events.create')) { ... }
     *
     * ملاحظة: super_admin له كل الصلاحيات تلقائياً
     */
    public function hasPermission(string $permissionName): bool
    {
        // super_admin له كل الصلاحيات
        if ($this->name === self::SUPER_ADMIN) {
            return true;
        }

        return $this->permissions()
            ->where('name', $permissionName)
            ->exists();
    }
}
