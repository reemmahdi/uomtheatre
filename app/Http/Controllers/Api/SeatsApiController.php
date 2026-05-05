<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;

class SeatsApiController extends Controller
{
    /**
     * إرجاع الحجوزات للخارطة
     * GET /api/seats/{eventId}
     */
    public function show(int $eventId): JsonResponse
    {
        $event = Event::with('status')->find($eventId);

        if (!$event) {
            return response()->json([
                'error' => 'الفعالية غير موجودة',
                'reservations' => [],
            ], 404);
        }

        // جلب حجوزات VIP فقط (type=vip_guest)
        $reservations = Reservation::with('seat.section')
            ->where('event_id', $eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->get();

        // بناء خريطة الحجوزات
        $reservationsMap = [];

        foreach ($reservations as $reservation) {
            $seat = $reservation->seat;
            if (!$seat || !$seat->section) {
                continue;
            }

            // المفتاح: "A-10-5" (يطابق ID في الخارطة)
            $key = "{$seat->section->name}-{$seat->row_number}-{$seat->seat_number}";

            $reservationsMap[$key] = [
                'guest_name' => $reservation->guest_name ?? 'وفد',
                'status' => $reservation->status === 'checked_in' ? 'checked_in' : 'reserved',
            ];
        }

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
            ],
            'reservations' => $reservationsMap,
            'count' => count($reservationsMap),
        ]);
    }
}
