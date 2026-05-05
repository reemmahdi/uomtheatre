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
    public function mount()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'receptionist', 'theater_manager'])) {
            return redirect()->route('dashboard');
        }

        return redirect('/seats-map');
    }

    public function render()
    {
        return redirect('/seats-map');
    }
}
