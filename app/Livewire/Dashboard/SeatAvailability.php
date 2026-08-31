<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Seat;
use App\Services\EventSeatAvailabilityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('تحديد المقاعد المتاحة')]
class SeatAvailability extends BaseComponent
{
    public Event $event;
    public string $eventUuid;

    public function mount(string $eventUuid)
    {
        $this->eventUuid = $eventUuid;
        $this->event = Event::where('uuid', $eventUuid)->firstOrFail();

        if (!Auth::user()->can('manageVipSeats', $this->event)) {
            abort(403, 'غير مصرح لك');
        }

        if ($this->event->status->name !== 'active') {
            abort(403, 'تحديد المقاعد متاح بعد موافقة الإدارة وقبل النشر فقط');
        }

        $service = app(EventSeatAvailabilityService::class);
        if (!$service->isInitialized($this->event)) {
            $service->initializeForEvent($this->event);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.seat-availability', [
            'event' => $this->event,
        ]);
    }
}
