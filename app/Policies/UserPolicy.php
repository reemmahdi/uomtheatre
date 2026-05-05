<?php

namespace App\Policies;

use App\Models\User;

/**
 * ============================================================
 * UserPolicy - UOMTheatre
 * ============================================================
 * قواعد صلاحيات إدارة المستخدمين والموظفين
 *
 * الأدوار:
 * - super_admin : كامل الصلاحيات (إنشاء، تعديل، تفعيل، تعطيل)
 * - الباقي      : لا صلاحيات إدارية
 *
 * ملاحظات أمنية:
 * - منع super_admin من تعطيل حسابه الشخصي
 * - منع تعديل حسابات السوبر أدمن من غير سوبر أدمن
 *
 * طريقة الاستخدام في Livewire:
 *   $this->authorize('viewAny', User::class);
 *   $this->authorize('toggleStatus', $user);
 * ============================================================
 */
class UserPolicy
{
    /**
     * صلاحية مطلقة للسوبر أدمن
     */
    public function before(User $user, string $ability): ?bool
    {
        // السوبر أدمن يمر بكل الـ abilities ما عدا تعطيل حسابه الشخصي
        if ($user->role->name === 'super_admin') {
            // الاستثناء الوحيد: لا يقدر يعطّل نفسه
            if ($ability === 'toggleStatus') {
                return null; // اتركها للقاعدة الأصلية لتفحص
            }
            return true;
        }
        return null;
    }

    /**
     * عرض قائمة المستخدمين/الموظفين - مدير النظام فقط
     */
    public function viewAny(User $user): bool
    {
        return $user->role->name === 'super_admin';
    }

    /**
     * عرض مستخدم محدد - مدير النظام فقط
     */
    public function view(User $user, User $target): bool
    {
        return $user->role->name === 'super_admin';
    }

    /**
     * إنشاء موظف جديد - مدير النظام فقط
     */
    public function create(User $user): bool
    {
        return $user->role->name === 'super_admin';
    }

    /**
     * تعديل بيانات موظف - مدير النظام فقط
     */
    public function update(User $user, User $target): bool
    {
        return $user->role->name === 'super_admin';
    }

    /**
     * حذف موظف - مدير النظام فقط، ولا يقدر يحذف نفسه
     */
    public function delete(User $user, User $target): bool
    {
        if ($user->role->name !== 'super_admin') {
            return false;
        }

        // لا يقدر يحذف حسابه الشخصي
        return $user->id !== $target->id;
    }

    /**
     * تفعيل/تعطيل حساب - مدير النظام، ولا يقدر يعطّل نفسه
     */
    public function toggleStatus(User $user, User $target): bool
    {
        if ($user->role->name !== 'super_admin') {
            return false;
        }

        // 🛡️ حماية مهمة: منع تعطيل الحساب الشخصي
        return $user->id !== $target->id;
    }
}
