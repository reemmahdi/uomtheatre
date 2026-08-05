<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeatAvailability;
use App\Models\Reservation;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SeatMapController extends Controller
{
    public function getSeatMap($eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);

        // مقاعد الوفود المحجوزة لهذه الفعالية
        $vipBookedSeatIds = Reservation::where('event_id', $eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->pluck('seat_id')
            ->toArray();

        // المقاعد المحجوبة عن الجمهور لهذه الفعالية (من صفحة تحديد المقاعد)
        $excludedSeatIds = EventSeatAvailability::where('event_id', $eventId)
            ->where('is_public_available', false)
            ->pluck('seat_id')
            ->toArray();

        $sections = Section::with('seats')->get();

        $seatMap = $sections->map(function ($section) use ($eventId, $vipBookedSeatIds, $excludedSeatIds) {
            return [
                'id'          => $section->id,
                'name'        => $section->name,
                'is_vip'      => $section->is_vip,
                'total_seats' => $section->total_seats,
                'available'   => $section->availableSeatsForEvent($eventId),
                'rows'        => $section->seats
                    ->groupBy('row_number')
                    ->map(function ($seats, $rowNumber) use ($eventId, $vipBookedSeatIds, $excludedSeatIds, $section) {
                        return [
                            'row_number' => $rowNumber,
                            'seats'      => $seats->map(function ($seat) use ($eventId, $vipBookedSeatIds, $excludedSeatIds, $section) {
                                // وفود: قسم كامل موسوم، أو مقعد موسوم، أو حجز وفد فعلي
                                $isVip = (bool) ($section->is_vip
                                    || $seat->is_vip_reserved
                                    || in_array($seat->id, $vipBookedSeatIds, true));

                                // محجوب عن الجمهور لهذه الفعالية؟
                                $isExcluded = in_array($seat->id, $excludedSeatIds, true);

                                return [
                                    'id'          => $seat->id,
                                    'seat_number' => $seat->seat_number,
                                    'label'       => $seat->label,
                                    // المحجوب لا يصل أبداً بحالة "متاح"
                                    'status'      => $isExcluded
                                        ? 'unavailable'
                                        : $seat->statusForEvent($eventId),
                                    'is_excluded'      => $isExcluded,
                                    'is_vip_reserved'  => $isVip,
                                    'is_vip_for_event' => in_array($seat->id, $vipBookedSeatIds, true),
                                ];
                            })->values(),
                        ];
                    })->values(),
            ];
        });

        return response()->json([
            'event'    => $event->title,
            'sections' => $seatMap,
        ]);
    }
}