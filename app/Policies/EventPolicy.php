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
 * - event_manager     : يراجع، يقبل، ينشر، يلغي، يدير الوفود
 * - receptionist      : عرض فقط
 * - university_office : عرض فقط للإحصائيات
 * - user              : لا يصل لها
 *
 * طريقة الاستخدام في Livewire:
 *   $this->authorize('update', $event);
 *   $this->authorize('delete', $event);
 *   $this->authorize('publish', $event);
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
        return null; // لا قرار - استمر للقواعد الأخرى
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

        // مدير المسرح يعدّل فعالياته فقط
        if ($event->created_by !== $user->id) {
            return false;
        }

        // يعدّل فقط لو الفعالية بحالة "مسودة"
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
     * بدء مراجعة فعالية (added → under_review)
     * مدير الإعلام فقط
     */
    public function review(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'added';
    }

    /**
     * قبول فعالية (under_review → active)
     * مدير الإعلام فقط
     */
    public function approve(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'under_review';
    }

    /**
     * نشر فعالية (active → published)
     * مدير الإعلام فقط
     */
    public function publish(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'active';
    }

    /**
     * إغلاق فعالية (published → closed)
     * مدير الإعلام فقط
     */
    public function close(User $user, Event $event): bool
    {
        return $user->role->name === 'event_manager'
            && $event->status->name === 'published';
    }

    /**
     * إلغاء فعالية - مدير الإعلام فقط، لأي حالة عدا المغلقة والمنتهية
     */
    public function cancel(User $user, Event $event): bool
    {
        if ($user->role->name !== 'event_manager') {
            return false;
        }

        return !in_array($event->status->name, ['cancelled', 'closed', 'end']);
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
            && in_array($event->status->name, ['active', 'under_review', 'published']);
    }
}
