<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('خارطة المقاعد')]
class SeatsDisplay extends BaseComponent
{
    protected array $allowedRoles = ['super_admin', 'receptionist', 'theater_manager'];

    public function mount()
    {
        return redirect('/seats-map');
    }

    public function render()
    {
        return redirect('/seats-map');
    }
}
