<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Event;

class SeatMapController extends Controller
{
    public function getSeatMap($eventId)
    {
        $event = Event::findOrFail($eventId);
        $sections = Section::with('seats')->get();

        $seatMap = $sections->map(function ($section) use ($eventId) {
            return [
                'id'          => $section->id,
                'name'        => $section->name,
                'is_vip'      => $section->is_vip,
                'total_seats' => $section->total_seats,
                'available'   => $section->availableSeatsForEvent($eventId),
                'rows'        => $section->seats
                    ->groupBy('row_number')
                    ->map(function ($seats, $rowNumber) use ($eventId) {
                        return [
                            'row_number' => $rowNumber,
                            'seats'      => $seats->map(function ($seat) use ($eventId) {
                                return [
                                    'id'              => $seat->id,
                                    'seat_number'     => $seat->seat_number,
                                    'label'           => $seat->label,
                                    'status'          => $seat->statusForEvent($eventId),
                                    'is_vip_reserved' => $seat->is_vip_reserved,
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
