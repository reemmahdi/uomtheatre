<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;

/**
 * ════════════════════════════════════════════════════════════════
 * EventPolicy — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   - استخدام nullsafe operator (?->) في كل status checks
 *   - استخدام Event::isDraft()/isPublished()/...etc بدل التحقق المباشر
 *     (هذه الدوال أصبحت nullsafe بعد إصلاح Event Model)
 *   - استخدام EventApproval::STATUS_PENDING بدل string literal
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventPolicy
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

    // ════════════════════════════════════════════════════════════
    // عرض الفعاليات
    // ════════════════════════════════════════════════════════════

    public function viewAny(User $user): bool
    {
        // ✨ nullsafe + استخدام constants
        return in_array($user->role?->name, [
            Role::EVENT_MANAGER,
            Role::THEATER_MANAGER,
            Role::UNIVERSITY_OFFICE,
            Role::RECEPTIONIST,
        ], true);
    }

    public function view(User $user, Event $event): bool
    {
        return $this->viewAny($user);
    }

    // ════════════════════════════════════════════════════════════
    // إنشاء / تعديل / حذف
    // ════════════════════════════════════════════════════════════

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::EVENTS_CREATE);
    }

    public function update(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_EDIT)) {
            return false;
        }

        // فعالياته فقط + مسودة فقط
        return $event->created_by === $user->id
            && $event->isDraft();
    }

    public function delete(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_DELETE)) {
            return false;
        }

        return $event->created_by === $user->id
            && $event->isDraft();
    }

    // ════════════════════════════════════════════════════════════
    // إرسال للموافقة (draft → added)
    // ════════════════════════════════════════════════════════════

    public function send(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_SEND_FOR_APPROVAL)) {
            return false;
        }

        return $event->created_by === $user->id
            && $event->isDraft();
    }

    // ════════════════════════════════════════════════════════════
    // الموافقات (مسرح / مكتب الرئيس)
    // ════════════════════════════════════════════════════════════

    public function approveAsTheater(User $user, Event $event): bool
    {
        return $this->canApproveWithPermission(
            $user,
            $event,
            Permission::EVENTS_APPROVE_THEATER
        );
    }

    public function approveAsOffice(User $user, Event $event): bool
    {
        return $this->canApproveWithPermission(
            $user,
            $event,
            Permission::EVENTS_APPROVE_OFFICE
        );
    }

    /**
     * ✨ helper مشترك (DRY): شروط الموافقة لأي دور
     */
    protected function canApproveWithPermission(User $user, Event $event, string $permission): bool
    {
        if (!$user->hasPermission($permission)) {
            return false;
        }

        // الفعالية يجب أن تكون في حالة pending approval
        if (!$event->isPendingApproval()) {
            return false;
        }

        // التحقق من وجود طلب موافقة pending لدور المستخدم
        return $event->approvals()
            ->where('role_id', $user->role_id)
            ->where('status', EventApproval::STATUS_PENDING)
            ->exists();
    }

    // ════════════════════════════════════════════════════════════
    // النشر / الإغلاق
    // ════════════════════════════════════════════════════════════

    public function publish(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_PUBLISH)) {
            return false;
        }

        return $event->status?->name === Status::ACTIVE;
    }

    public function close(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_PUBLISH)) {
            return false;
        }

        return $event->isPublished();
    }

    // ════════════════════════════════════════════════════════════
    // الإلغاء
    // ════════════════════════════════════════════════════════════

    public function cancel(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_CANCEL)) {
            return false;
        }

        // ✨ nullsafe
        return !in_array($event->status?->name, [
            Status::CANCELLED,
            Status::CLOSED,
            Status::END,
        ], true);
    }

    // ════════════════════════════════════════════════════════════
    // إيقاف / استئناف الحجز
    // ════════════════════════════════════════════════════════════

    public function pauseBooking(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_PUBLISH)) {
            return false;
        }

        return $event->isPublished()
            && !$event->is_booking_paused;
    }

    public function resumeBooking(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_PUBLISH)) {
            return false;
        }

        // ✨ مُحسّن: لا نسمح بـ resume إلا لو الفعالية published
        //    (سابقاً كان يسمح حتى لو كانت cancelled مع is_booking_paused)
        return (bool) $event->is_booking_paused
            && $event->isPublished();
    }

    // ════════════════════════════════════════════════════════════
    // إدارة مقاعد الوفود
    // ════════════════════════════════════════════════════════════

    public function manageVipSeats(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::VIP_MANAGE)) {
            return false;
        }

        return in_array($event->status?->name, [
            Status::ACTIVE,
            Status::PUBLISHED,
        ], true);
    }
}
