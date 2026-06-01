<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;

class EventPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
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

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::EVENTS_CREATE);
    }

    public function update(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_EDIT)) {
            return false;
        }

        return $event->created_by === $user->id
            && in_array($event->status?->name, [Status::DRAFT, Status::REJECTED], true);
    }

    public function delete(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_DELETE)) {
            return false;
        }

        return $event->created_by === $user->id
            && in_array($event->status?->name, [Status::DRAFT, Status::REJECTED], true);
    }

    public function send(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_SEND_FOR_APPROVAL)) {
            return false;
        }

        return $event->created_by === $user->id
            && in_array($event->status?->name, [Status::DRAFT, Status::REJECTED], true);
    }

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

    protected function canApproveWithPermission(User $user, Event $event, string $permission): bool
    {
        if (!$user->hasPermission($permission)) {
            return false;
        }

        if (!$event->isPendingApproval()) {
            return false;
        }

        return $event->approvals()
            ->where('role_id', $user->role_id)
            ->where('status', EventApproval::STATUS_PENDING)
            ->exists();
    }

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

    public function cancel(User $user, Event $event): bool
    {
        if (!$user->hasPermission(Permission::EVENTS_CANCEL)) {
            return false;
        }

        return !in_array($event->status?->name, [
            Status::CANCELLED,
            Status::CLOSED,
            Status::END,
        ], true);
    }

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

        return (bool) $event->is_booking_paused
            && $event->isPublished();
    }

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
