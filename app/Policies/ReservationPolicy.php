<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * ============================================================
 * ReservationPolicy - UOMTheatre
 * ============================================================
 * قواعد صلاحيات الحجوزات (مقاعد الوفود + الحجز العادي + الحضور)
 *
 * الأدوار:
 * - super_admin   : كل شي
 * - event_manager : إدارة حجوزات الوفود فقط
 * - receptionist  : تسجيل الحضور (check-in) فقط
 * - user          : حجز/إلغاء حجوزاته الشخصية فقط
 * ============================================================
 */
class ReservationPolicy
{
    /**
     * صلاحية مطلقة للسوبر أدمن
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role->name === 'super_admin') {
            return true;
        }
        return null;
    }

    /**
     * عرض قائمة الحجوزات
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, [
            'event_manager',
            'receptionist',
            'university_office',
        ]);
    }

    /**
     * عرض حجز محدد
     */
    public function view(User $user, Reservation $reservation): bool
    {
        // مدير الإعلام والاستقبال يشوفون كل الحجوزات
        if (in_array($user->role->name, ['event_manager', 'receptionist'])) {
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
        return $user->role->name === 'event_manager';
    }

    /**
     * إلغاء حجز وفود - مدير الإعلام فقط
     */
    public function cancelVipBooking(User $user, Reservation $reservation): bool
    {
        return $user->role->name === 'event_manager'
            && $reservation->type === 'vip_guest'
            && $reservation->status !== 'cancelled';
    }

    /**
     * تسجيل الحضور - موظف الاستقبال فقط
     */
    public function checkIn(User $user, Reservation $reservation): bool
    {
        return $user->role->name === 'receptionist'
            && $reservation->status === 'confirmed';
    }

    /**
     * إرسال إشعار واتساب - مدير الإعلام فقط
     */
    public function sendNotification(User $user, Reservation $reservation): bool
    {
        return $user->role->name === 'event_manager';
    }
}
