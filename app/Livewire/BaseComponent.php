<?php

namespace App\Livewire;

use Livewire\Component;
use App\Traits\WithSweetAlert;

abstract class BaseComponent extends Component
{
    use WithSweetAlert;

    protected array $allowedRoles = [];

    public function booted(): void
    {
        if ($this->allowedRoles === []) {
            return;
        }
        $role = auth()->user()?->role?->name;
        abort_unless($role !== null && in_array($role, $this->allowedRoles, true), 403);
    }
}
