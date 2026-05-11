<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * ════════════════════════════════════════════════════════════════
 * DashboardController — UOMTheatre API (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 إزالة hardcoded role_id=6 → Role::USER ديناميكي
 *   🟡 إزالة magic numbers (997, 52) → Event::TOTAL_SEATS + config
 *   🟡 nullsafe على relationships
 *
 * ════════════════════════════════════════════════════════════════
 */
class DashboardController extends Controller
{
    public function eventDashboard($id): JsonResponse
    {
        $event = Event::with('status')->findOrFail($id);

        // ✨ استخدام Event::TOTAL_SEATS بدل magic number
        $totalSeats = defined(Event::class . '::TOTAL_SEATS') ? Event::TOTAL_SEATS : 997;

        $reserved   = $event->reservedSeatsCount();
        $checkedIn  = $event->checkedInCount();

        return response()->json([
            'event'           => $event->title,
            'status'          => $event->status?->display_name,
            'total_seats'     => $totalSeats,
            'reserved'        => $reserved,
            'available'       => $totalSeats - $reserved,
            'checked_in'      => $checkedIn,
            'occupancy_rate'  => $totalSeats > 0 ? round(($reserved / $totalSeats) * 100, 1) : 0,
            'attendance_rate' => $reserved > 0 ? round(($checkedIn / $reserved) * 100, 1) : 0,
            'vip_guests'      => $event->reservations()
                ->where('type', 'vip_guest')
                ->where('status', '!=', 'cancelled')
                ->count(),
        ]);
    }

    public function overview(): JsonResponse
    {
        $publishedStatus = Status::where('name', Status::PUBLISHED)->first();

        // ✨ مُصحَّح: ديناميكي بدل hardcoded 6
        $userRoleId = Role::where('name', Role::USER)->value('id');

        $totalSeats = defined(Event::class . '::TOTAL_SEATS') ? Event::TOTAL_SEATS : 997;
        $vipSeats   = config('theatre.vip_seats', 52);

        return response()->json([
            'total_events'       => Event::count(),
            'published_events'   => $publishedStatus
                ? Event::where('status_id', $publishedStatus->id)->count()
                : 0,
            'total_users'        => $userRoleId
                ? User::where('role_id', $userRoleId)->count()
                : 0,
            'total_reservations' => Reservation::where('status', '!=', 'cancelled')->count(),
            'total_checked_in'   => Reservation::where('status', 'checked_in')->count(),
            'total_seats'        => $totalSeats,
            'vip_seats'          => $vipSeats,
        ]);
    }
}
