<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Seat;
use App\Services\EventSeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ════════════════════════════════════════════════════════════════
 * SeatAvailabilityController — UOMTheatre
 * ════════════════════════════════════════════════════════════════
 *
 * API endpoints لإدارة إتاحة المقاعد للجمهور:
 *
 *   GET  /api/events/{uuid}/availability      → جلب المستبعدات
 *   POST /api/events/{uuid}/availability/save → حفظ التغييرات
 *
 * ملاحظة: كل المقاعد متساوية - لا يوجد فلترة VIP
 *
 * ════════════════════════════════════════════════════════════════
 */
class SeatAvailabilityController extends Controller
{
    /**
     * GET — جلب قائمة المقاعد المستبعدة لفعالية
     */
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

        // جلب كل المقاعد المستبعدة
        $excludedSeatIds = $service->getExcludedSeatIds($event);

        // تحويل IDs إلى مفاتيح بصيغة "A-10-5"
        $excludedKeys = Seat::whereIn('id', $excludedSeatIds)
            ->with('section')
            ->get()
            ->map(function ($seat) {
                return "{$seat->section->name}-{$seat->row_number}-{$seat->seat_number}";
            })
            ->values()
            ->toArray();

        $totalSeats = Seat::count();

        return response()->json([
            'event_title'        => $event->title,
            'excluded_seat_keys' => $excludedKeys,
            'excluded_count'     => count($excludedKeys),
            'available_count'    => $totalSeats - count($excludedKeys),
            'total_seats'        => $totalSeats,
        ]);
    }

    /**
     * POST — حفظ التغييرات
     */
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

        // تحويل المفاتيح إلى seat IDs
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

        // 1. أولاً: جعل كل المقاعد متاحة
        $allSeatIds = Seat::pluck('id')->toArray();
        $service->bulkUpdate($event, $allSeatIds, true);

        // 2. ثم: استبعاد فقط ما يجب استبعاده
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
