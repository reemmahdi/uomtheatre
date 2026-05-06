<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * ============================================================
 * EventPolicy - UOMTheatre
 * ============================================================
 * قواعد صلاحيات الفعاليات
 *
 * الأدوار:
 * - super_admin       : كامل الصلاحيات
 * - theater_manager   : ينشئ فعالياته + يعدّلها (لو مسودة) + يحذفها (لو مسودة)
 *                       + يلغيها (لو مسودة أو مضافة فقط)
 * - event_manager     : يقبل، ينشر، يغلق، يلغي، يدير الوفود
 * - receptionist      : عرض فقط
 * - university_office : عرض فقط للإحصائيات
 * - user              : لا يصل لها
 * ============================================================
 */
class EventPolicy
{
    /**
     * صلاحية مطلقة للسوبر أدمن - تنطبق قبل أي قاعدة أخرى
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role->name === 'super_admin') {
            return true;
        }
        return null;
    }

    /**
     * عرض قائمة الفعاليات
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role->name, [
            'theater_manager',
            'event_manager',
            'receptionist',
            'university_office',
        ]);
    }

    /**
     * عرض فعالية محددة
     */
    public function view(User $user, Event $event): bool
    {
        return in_array($user->role->name, [
            'theater_manager',
            'event_manager',
            'receptionist',
            'university_office',
        ]);
    }

    /**
     * إنشاء فعالية جديدة - مدير المسرح فقط
     */
    public function create(User $user): bool
    {
        return $user->role->name === 'theater_manager';
    }

    /**
     * تعديل فعالية - مدير المسرح فقط، فعالياته فقط، ولو هي مسودة فقط
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->role->name !== 'theater_manager') {
            return false;
        }

        if ($event->created_by !== $user->id) {
            return false;
        }

        return $event->status->name === 'draft';
    }

    /**
     * حذف فعالية - مدير المسرح، فعالياته فقط، ولو هي مسودة
     */
    public function delete(User $user, Event $event): bool
    {
        if ($user->role->name !== 'theater_manager') {
            return false;
        }

        return $event->created_by === $user->id
            && $event->status->name === 'draft';
    }

    /**
     * إرسال الفعالية للمراجعة (draft → added)
     * مدير المسرح فقط، فعالياته فقط
     */
    public function send(User $user, Event $event): bool
    {
        return $user->role->name === 'theater_manager'
            && $event->created_by === $user->id
            && $event->status->name === 'draft';
    }

    /**
     * قبول فعالية (added → active) - مدير الإعلام
     * (تجاوز مرحلة under_review التي تم حذفها)
     */
    public function approve(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'added';
    }

    /**
     * نشر فعالية (active → published) - مدير الإعلام
     */
    public function publish(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'active';
    }

    /**
     * إغلاق فعالية (published → closed) - مدير الإعلام
     */
    public function close(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'published';
    }

    /**
     * ✨ إلغاء فعالية (محدّثة - منطق متعدد الأدوار)
     *
     * مدير المسرح:
     *   - يلغي فعالياته فقط
     *   - فقط لو الفعالية بحالة "مسودة" أو "مضافة"
     *   (بعد ما تتحول لـ active/published تنتقل لمسؤولية مدير الإعلام)
     *
     * مدير الإعلام:
     *   - يلغي أي فعالية
     *   - عدا المغلقة والمنتهية والملغاة
     */
    public function cancel(User $user, Event $event): bool
    {
        // مدير الإعلام: صلاحيات واسعة
        if ($user->role->name === 'event_manager') {
            return !in_array($event->status->name, ['cancelled', 'closed', 'end']);
        }

        // مدير المسرح: صلاحيات محدودة بفعالياته الجديدة
        if ($user->role->name === 'theater_manager') {
            return $event->created_by === $user->id
                && in_array($event->status->name, ['draft', 'added']);
        }

        return false;
    }

    /**
     * إيقاف الحجز مؤقتاً - مدير الإعلام فقط، للمنشورة فقط
     */
    public function pauseBooking(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'published'
            && !$event->is_booking_paused;
    }

    /**
     * استئناف الحجز - مدير الإعلام فقط
     */
    public function resumeBooking(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->is_booking_paused;
    }

    /**
     * إدارة مقاعد الوفود - مدير الإعلام فقط
     */
    public function manageVipSeats(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && in_array($event->status->name, ['active', 'published']);
    }
}
