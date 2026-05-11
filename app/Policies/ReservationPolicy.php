<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;

/**
 * ════════════════════════════════════════════════════════════
 * ReservationPolicy — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   - استخدام Role::* constants بدل string literals
 *   - استخدام $user->is*() helpers بدل التحقق المباشر
 *   - nullsafe operator في كل مكان
 *
 * قواعد صلاحيات الحجوزات:
 *   - super_admin   : كل شي
 *   - event_manager : إدارة حجوزات الوفود فقط
 *   - receptionist  : تسجيل الحضور (check-in) فقط
 *   - user          : حجز/إلغاء حجوزاته الشخصية فقط
 *
 * ════════════════════════════════════════════════════════════
 */
class ReservationPolicy
{
    /**
     * صلاحية مطلقة للسوبر أدمن
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return null;
    }

    /**
     * عرض قائمة الحجوزات
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, [
            Role::EVENT_MANAGER,
            Role::RECEPTIONIST,
            Role::UNIVERSITY_OFFICE,
        ], true);
    }

    /**
     * عرض حجز محدد
     */
    public function view(User $user, Reservation $reservation): bool
    {
        // مدير الإعلام والاستقبال يشوفون كل الحجوزات
        if ($user->isEventManager() || $user->isReceptionist()) {
            return true;
        }

        // المستخدم العادي يشوف حجوزاته فقط
        return $user->id === $reservation->user_id;
    }

    /**
     * إنشاء حجز وفود - مدير الإعلام فقط
     */
    public function createVipBooking(User $user): bool
    {
        return $user->isEventManager();
    }

    /**
     * إلغاء حجز وفود - مدير الإعلام فقط
     */
    public function cancelVipBooking(User $user, Reservation $reservation): bool
    {
        return $user->isEventManager()
            && $reservation->type === 'vip_guest'
            && $reservation->status !== 'cancelled';
    }

    /**
     * تسجيل الحضور - موظف الاستقبال فقط
     */
    public function checkIn(User $user, Reservation $reservation): bool
    {
        return $user->isReceptionist()
            && $reservation->status === 'confirmed';
    }

    /**
     * إرسال إشعار واتساب - مدير الإعلام فقط
     */
    public function sendNotification(User $user, Reservation $reservation): bool
    {
        return $user->isEventManager();
    }
}
