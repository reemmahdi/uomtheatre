<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Seat;
use App\Services\EventSeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeatAvailabilityController extends Controller
{
    public function show(string $eventUuid): JsonResponse
    {
        $event = Event::where('uuid', $eventUuid)->firstOrFail();

        if (!Auth::check() || !Auth::user()->can('manageVipSeats', $event)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $service = app(EventSeatAvailabilityService::class);

        if (!$service->isInitialized($event)) {
            $service->initializeForEvent($event);
        }

        $excludedSeatIds = $service->getExcludedSeatIds($event);

        $excludedKeys = Seat::whereIn('id', $excludedSeatIds)
            ->pluck('label')
            ->filter()
            ->values()
            ->toArray();

        $vipSeatKeys = Seat::where('is_vip_reserved', true)
            ->pluck('label')
            ->filter()
            ->values()
            ->toArray();

        $totalSeats   = Seat::count();
        $vipCount     = count($vipSeatKeys);
        $excludedCount = count($excludedKeys);

        return response()->json([
            'event_title'        => $event->title,
            'excluded_seat_keys' => $excludedKeys,
            'vip_seat_keys'      => $vipSeatKeys,
            'vip_count'          => $vipCount,
            'excluded_count'     => $excludedCount,
            'available_count'    => $totalSeats - $vipCount - $excludedCount,
            'seats'              => Seat::with('section')
                ->orderBy('section_id')
                ->orderBy('row_number')
                ->orderBy('seat_number')
                ->get()
                ->map(fn($s) => [
                    'label'   => $s->label,
                    'section' => $s->section->name,
                    'row'     => (int) $s->row_number,
                    'num'     => (int) $s->seat_number,
                    'vip'     => (bool) $s->is_vip_reserved,
                ])
                ->values(),
            'total_seats'        => $totalSeats,
        ]);
    }

    public function save(Request $request, string $eventUuid): JsonResponse
    {
        $event = Event::where('uuid', $eventUuid)->firstOrFail();

        if (!Auth::check() || !Auth::user()->can('manageVipSeats', $event)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'excluded_keys'   => 'array',
            'excluded_keys.*' => 'string|regex:/^[A-F]-\d+-\d+$/',
        ]);

        $excludedKeys = $validated['excluded_keys'] ?? [];

        $excludedSeatIds = [];
        foreach ($excludedKeys as $key) {
            [$sectionName, $row, $num] = explode('-', $key);
            $seat = Seat::whereHas('section', fn($q) => $q->where('name', $sectionName))
                ->where('row_number', (int) $row)
                ->where('seat_number', (int) $num)
                ->first();

            if ($seat) {
                $excludedSeatIds[] = $seat->id;
            }
        }

        $service = app(EventSeatAvailabilityService::class);

        $allSeatIds = Seat::pluck('id')->toArray();
        $service->bulkUpdate($event, $allSeatIds, true);

        if (!empty($excludedSeatIds)) {
            $service->bulkUpdate($event, $excludedSeatIds, false, 'استبعد بواسطة مدير الإعلام');
        }

        return response()->json([
            'success'         => true,
            'message'         => 'تم حفظ التغييرات بنجاح',
            'excluded_count'  => count($excludedSeatIds),
            'available_count' => Seat::count() - count($excludedSeatIds),
        ]);
    }
}
