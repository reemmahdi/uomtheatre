<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeatAvailability;
use App\Models\Reservation;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;

class SeatsApiController extends Controller
{
    public function show(int $eventId): JsonResponse
    {
        $event = Event::with('status')->find($eventId);

        if (!$event) {
            return response()->json([
                'error' => 'الفعالية غير موجودة',
                'reservations' => [],
            ], 404);
        }

        $reservations = Reservation::with(['seat.section', 'user'])
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->get();

        $reservationsMap = [];

        foreach ($reservations as $reservation) {
            $seat = $reservation->seat;
            if (!$seat || !$seat->section) {
                continue;
            }

            $key = "{$seat->section->name}-{$seat->row_number}-{$seat->seat_number}";

            $reservationsMap[$key] = [
                'guest_name' => $reservation->type === 'vip_guest'
                    ? ($reservation->guest_name ?? 'وفد')
                    : ($reservation->user?->name ?? 'جمهور'),
                'status' => $reservation->status === 'checked_in' ? 'checked_in' : 'reserved',
            ];
        }

        $vipSeatIds = Seat::with('section')
            ->where('is_vip_reserved', true)
            ->get()
            ->filter(fn($s) => $s->section)
            ->map(fn($s) => "{$s->section->name}-{$s->row_number}-{$s->seat_number}")
            ->values();

        $blockedSeatIds = EventSeatAvailability::with('seat.section')
            ->where('event_id', $eventId)
            ->where('is_public_available', false)
            ->get()
            ->filter(fn($a) => $a->seat && $a->seat->section)
            ->map(fn($a) => "{$a->seat->section->name}-{$a->seat->row_number}-{$a->seat->seat_number}")
            ->values();

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
            ],
            'reservations' => $reservationsMap,
            'vip_seat_ids' => $vipSeatIds,
            'blocked_seat_ids' => $blockedSeatIds,
            'count' => count($reservationsMap),
        ]);
    }
}
