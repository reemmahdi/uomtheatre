<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;

class ReservationPolicy
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
            Role::RECEPTIONIST,
            Role::UNIVERSITY_OFFICE,
        ], true);
    }

    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->isEventManager() || $user->isReceptionist()) {
            return true;
        }

        return $user->id === $reservation->user_id;
    }

    public function createVipBooking(User $user): bool
    {
        return $user->isEventManager();
    }

    public function cancelVipBooking(User $user, Reservation $reservation): bool
    {
        return $user->isEventManager()
            && $reservation->type === 'vip_guest'
            && $reservation->status !== 'cancelled';
    }

    public function checkIn(User $user, Reservation $reservation): bool
    {
        return $user->isReceptionist()
            && $reservation->status === 'confirmed';
    }

    public function sendNotification(User $user, Reservation $reservation): bool
    {
        return $user->isEventManager();
    }
}
