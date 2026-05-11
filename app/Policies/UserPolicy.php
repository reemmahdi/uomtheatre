<?php

namespace App\Policies;

use App\Models\User;

/**
 * ════════════════════════════════════════════════════════════
 * UserPolicy — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   - استخدام $user->isSuperAdmin() بدل التحقق المباشر
 *     (هذه الدالة nullsafe بعد إصلاح User Model)
 *
 * قواعد صلاحيات إدارة المستخدمين:
 *   - super_admin : كامل الصلاحيات
 *   - الباقي      : لا صلاحيات إدارية
 *
 * ملاحظات أمنية:
 *   - منع super_admin من تعطيل/حذف حسابه الشخصي
 *
 * ════════════════════════════════════════════════════════════
 */
class UserPolicy
{
    /**
     * صلاحية مطلقة للسوبر أدمن
     * (ما عدا تعطيل/حذف حسابه الشخصي)
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            // الاستثناءات: لا يقدر يعطّل/يحذف نفسه
            if (in_array($ability, ['toggleStatus', 'delete'], true)) {
                return null; // اتركها للقاعدة الأصلية لتفحص
            }
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * حذف موظف - مدير النظام فقط، ولا يقدر يحذف نفسه
     */
    public function delete(User $user, User $target): bool
    {
        if (!$user->isSuperAdmin()) {
            return false;
        }

        // 🛡️ حماية: لا يقدر يحذف حسابه الشخصي
        return $user->id !== $target->id;
    }

    /**
     * تفعيل/تعطيل حساب - مدير النظام، ولا يقدر يعطّل نفسه
     */
    public function toggleStatus(User $user, User $target): bool
    {
        if (!$user->isSuperAdmin()) {
            return false;
        }

        // 🛡️ حماية: منع تعطيل الحساب الشخصي
        return $user->id !== $target->id;
    }
}
