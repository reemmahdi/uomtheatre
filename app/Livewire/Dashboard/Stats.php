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
        if (!in_array(Auth::user()->role->name, ['super_admin', 'university_office'], true)) {
            return redirect()->route('dashboard');
        }

        $pub = Status::where('name', 'published')->first();

        $totals = Reservation::selectRaw(
            "event_id,
             COUNT(*) as booked,
             SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as attended"
        )
            ->where('status', '!=', 'cancelled')
            ->groupBy('event_id')
            ->get()
            ->keyBy('event_id');

        $eventStats = Event::with('status')
            ->orderByDesc('start_datetime')
            ->limit(30)
            ->get()
            ->map(function ($event) use ($totals) {
                $row = $totals->get($event->id);
                $booked = (int) ($row->booked ?? 0);
                $attended = (int) ($row->attended ?? 0);

                return [
                    'title'    => $event->title,
                    'date'     => $event->start_datetime?->format('Y-m-d'),
                    'status'   => $event->status->name ?? '',
                    'booked'   => $booked,
                    'attended' => $attended,
                    'rate'     => $booked > 0 ? (int) round($attended / $booked * 100) : 0,
                ];
            });

        $totalReservations = Reservation::where('status', '!=', 'cancelled')->count();
        $totalCheckedIn = Reservation::where('status', 'checked_in')->count();

        return view('livewire.dashboard.stats', [
            'totalEvents'       => Event::count(),
            'publishedEvents'   => $pub ? Event::where('status_id', $pub->id)->count() : 0,
            'totalReservations' => $totalReservations,
            'totalCheckedIn'    => $totalCheckedIn,
            'overallRate'       => $totalReservations > 0
                ? (int) round($totalCheckedIn / $totalReservations * 100)
                : 0,
            'eventStats'        => $eventStats,
        ]);
    }
}
