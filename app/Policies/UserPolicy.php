<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            if (in_array($ability, ['toggleStatus', 'delete'], true)) {
                return null;
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

    public function delete(User $user, User $target): bool
    {
        if (!$user->isSuperAdmin()) {
            return false;
        }

        return $user->id !== $target->id;
    }

    public function toggleStatus(User $user, User $target): bool
    {
        if (!$user->isSuperAdmin()) {
            return false;
        }

        return $user->id !== $target->id;
    }
}
