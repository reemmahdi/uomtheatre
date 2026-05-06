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
#[Title('مقاعد الوفود')]
class VipEvents extends BaseComponent
{
    // ✨ البحث
    public string $searchTitle = '';

    public function render()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            return redirect()->route('dashboard');
        }

        // الفعاليات اللي ممكن نحجز لها وفود (مو مسودة ومو ملغاة)
        $excludeStatuses = Status::whereIn('name', ['draft', 'cancelled', 'end'])->pluck('id');

        $query = Event::with(['status', 'creator'])
            ->whereNotIn('status_id', $excludeStatuses);

        // ✨ تطبيق فلتر البحث
        if (!empty($this->searchTitle)) {
            $query->where('title', 'like', '%' . $this->searchTitle . '%');
        }

        $events = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                $event->vip_booked = Reservation::where('event_id', $event->id)
                    ->where('type', 'vip_guest')
                    ->where('status', '!=', 'cancelled')
                    ->count();
                return $event;
            });

        return view('livewire.dashboard.vip-events', [
            'events' => $events,
            'totalVipSeats' => config('theatre.vip_seats', 52),
        ]);
    }
}
