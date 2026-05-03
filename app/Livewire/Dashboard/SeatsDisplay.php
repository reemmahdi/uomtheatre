<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Reservation;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('شاشة عرض المقاعد')]
class SeatsDisplay extends BaseComponent
{
    public ?int $selectedEventId = null;

    public function mount()
    {
        $publishedStatus = Status::where('name', 'published')->first();

        if ($publishedStatus) {
            $latestEvent = Event::where('status_id', $publishedStatus->id)
                ->orderBy('start_datetime', 'desc')
                ->first();

            if ($latestEvent) {
                $this->selectedEventId = $latestEvent->id;
            }
        }
    }

    public function selectEvent(int $eventId)
    {
        $this->selectedEventId = $eventId;
    }

    /**
     * بناء قسم بسيط - مقاعد صغيرة بدون تداخل
     */
    private function buildSection($seats, $reservations)
    {
        $rows = $seats->groupBy('row_number')->sortKeys();
        $rowsData = [];

        foreach ($rows as $rowNumber => $rowSeats) {
            $seatsArray = [];
            foreach ($rowSeats->sortBy('seat_number')->values() as $seat) {
                $reservation = $reservations->get($seat->id);
                $status = 'available';

                if ($seat->is_vip_reserved) {
                    $status = 'vip_available';
                }
                if ($reservation) {
                    if ($reservation->status === 'checked_in') {
                        $status = $reservation->type === 'vip_guest' ? 'vip_checked_in' : 'checked_in';
                    } else {
                        $status = $reservation->type === 'vip_guest' ? 'vip_booked' : 'booked';
                    }
                }

                $seatsArray[] = [
                    'id'          => $seat->id,
                    'label'       => $seat->label,
                    'seat_number' => $seat->seat_number,
                    'status'      => $status,
                    'guest_name'  => $reservation?->guest_name,
                ];
            }

            $rowsData[] = [
                'number' => $rowNumber,
                'seats'  => $seatsArray,
            ];
        }

        return $rowsData;
    }

    public function render()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'receptionist', 'event_manager'])) {
            return redirect()->route('dashboard');
        }

        $availableStatuses = Status::whereIn('name', ['active', 'published', 'under_review'])->pluck('id');
        $events = Event::with('status')
            ->whereIn('status_id', $availableStatuses)
            ->orderBy('start_datetime', 'desc')
            ->get();

        $event = null;
        $sections = [
            'A' => null, 'B' => null, 'C' => null,
            'D' => null, 'E' => null, 'F' => null,
        ];
        $stats = [
            'total' => 0, 'available' => 0, 'booked' => 0,
            'vip_total' => 0, 'vip_booked' => 0, 'checked_in' => 0,
        ];

        if ($this->selectedEventId) {
            $event = Event::with('status')->find($this->selectedEventId);

            if ($event) {
                $allSeats = Seat::with('section')
                    ->orderBy('section_id')
                    ->orderBy('row_number')
                    ->orderBy('seat_number')
                    ->get();

                $reservations = Reservation::where('event_id', $this->selectedEventId)
                    ->where('status', '!=', 'cancelled')
                    ->get()
                    ->keyBy('seat_id');

                $allSeatsBySection = $allSeats->groupBy(fn($s) => $s->section->name);

                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $name) {
                    if (isset($allSeatsBySection[$name])) {
                        $sections[$name] = $this->buildSection($allSeatsBySection[$name], $reservations);
                    }
                }

                $stats['total']        = $allSeats->count();
                $stats['vip_total']    = $allSeats->where('is_vip_reserved', true)->count();
                $stats['booked']       = $reservations->where('type', 'regular')->count();
                $stats['vip_booked']   = $reservations->where('type', 'vip_guest')->count();
                $stats['checked_in']   = $reservations->where('status', 'checked_in')->count();
                $stats['available']    = $stats['total'] - $stats['booked'] - $stats['vip_booked'];
            }
        }

        return view('livewire.dashboard.seats-display', [
            'events'   => $events,
            'event'    => $event,
            'sections' => $sections,
            'stats'    => $stats,
        ]);
    }
}
