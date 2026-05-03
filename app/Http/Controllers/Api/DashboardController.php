<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Status;

class DashboardController extends Controller
{
    public function eventDashboard($id)
    {
        $event = Event::with('status')->findOrFail($id);

        return response()->json([
            'event'          => $event->title,
            'status'         => $event->status->display_name,
            'total_seats'    => 997,
            'reserved'       => $event->reservedSeatsCount(),
            'available'      => 997 - $event->reservedSeatsCount(),
            'checked_in'     => $event->checkedInCount(),
            'occupancy_rate' => round(($event->reservedSeatsCount() / 997) * 100, 1),
            'attendance_rate'=> $event->reservedSeatsCount() > 0
                ? round(($event->checkedInCount() / $event->reservedSeatsCount()) * 100, 1)
                : 0,
            'vip_guests'     => $event->reservations()->where('type', 'vip_guest')->where('status', '!=', 'cancelled')->count(),
        ]);
    }

    public function overview()
    {
        $publishedStatus = Status::where('name', Status::PUBLISHED)->first();

        return response()->json([
            'total_events'      => Event::count(),
            'published_events'  => Event::where('status_id', $publishedStatus->id)->count(),
            'total_users'       => User::where('role_id', 6)->count(),
            'total_reservations'=> Reservation::where('status', '!=', 'cancelled')->count(),
            'total_checked_in'  => Reservation::where('status', 'checked_in')->count(),
            'total_seats'       => 997,
            'vip_seats'         => 52,
        ]);
    }
}
