<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

/**
 * ════════════════════════════════════════════════════════════════
 * SeatMapController — UOMTheatre API (إعادة هندسة)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 التعديل:
 *   - is_vip_reserved (column ثابت) → يُترك للـ Flutter القديم
 *   - is_vip_for_event (per-event) → جديد، يفحص reservations
 *   - status (statusForEvent) يحدد حالة المقعد فعلياً
 *
 * ════════════════════════════════════════════════════════════════
 */
class SeatMapController extends Controller
{
    public function getSeatMap($eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);

        // ✨ جلب كل الـ vip_guest reservations مرة واحدة (تحسين أداء)
        $vipBookedSeatIds = Reservation::where('event_id', $eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->pluck('seat_id')
            ->toArray();

        $sections = Section::with('seats')->get();

        $seatMap = $sections->map(function ($section) use ($eventId, $vipBookedSeatIds) {
            return [
                'id'          => $section->id,
                'name'        => $section->name,
                'is_vip'      => $section->is_vip,   // legacy - يُتجاهل في الـ logic الجديد
                'total_seats' => $section->total_seats,
                'available'   => $section->availableSeatsForEvent($eventId),
                'rows'        => $section->seats
                    ->groupBy('row_number')
                    ->map(function ($seats, $rowNumber) use ($eventId, $vipBookedSeatIds) {
                        return [
                            'row_number' => $rowNumber,
                            'seats'      => $seats->map(function ($seat) use ($eventId, $vipBookedSeatIds) {
                                return [
                                    'id'              => $seat->id,
                                    'seat_number'     => $seat->seat_number,
                                    'label'           => $seat->label,
                                    'status'          => $seat->statusForEvent($eventId),
                                    // ✨ legacy (للـ Flutter القديم - يُتجاهل في النموذج الجديد)
                                    'is_vip_reserved' => $seat->is_vip_reserved,
                                    // ✨ جديد: per-event - يجب أن يستخدمه Flutter
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
