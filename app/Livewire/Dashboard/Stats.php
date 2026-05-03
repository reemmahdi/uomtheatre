<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('الإحصائيات')]
class Stats extends BaseComponent
{
    public function render()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin','university_office'])) return redirect()->route('dashboard');
        $pub = Status::where('name','published')->first();
        return view('livewire.dashboard.stats', [
            'totalEvents'=>Event::count(),
            'publishedEvents'=>$pub ? Event::where('status_id',$pub->id)->count() : 0,
            'totalReservations'=>Reservation::where('status','!=','cancelled')->count(),
            'totalCheckedIn'=>Reservation::where('status','checked_in')->count(),
        ]);
    }
}
