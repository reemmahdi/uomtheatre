<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\Status;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 * EventController — UOMTheatre API (إعادة هندسة كاملة)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 التعديلات:
 *   - schema جديد (start_datetime/end_datetime)
 *   - publicIndex: حساب المتاح بدل اعتماد is_vip_reserved
 *   - backwards compatibility (event_date/event_time للـ Flutter)
 *   - Policy authorize
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventController extends Controller
{
    /**
     * تحويل Event إلى array - استخدام مشترك
     */
    private function eventToArray(Event $event, bool $detailed = false): array
    {
        $data = [
            'id'              => $event->id,
            'uuid'            => $event->uuid,
            'title'           => $event->title,
            'description'     => $event->description,
            'start_datetime'  => $event->start_datetime?->toIso8601String(),
            'end_datetime'    => $event->end_datetime?->toIso8601String(),
            'event_date'      => $event->start_datetime?->format('Y-m-d'),
            'event_time'      => $event->start_datetime?->format('H:i'),
            'status'          => $event->status?->display_name,
            'status_name'     => $event->status?->name,
            'created_by'      => $event->creator?->name,
            'reserved_seats'  => $event->reservedSeatsCount(),
            'available_seats' => $event->availableSeatsCount(),
            'occupancy_rate'  => $event->occupancyRate(),
            'is_booking_paused' => (bool) $event->is_booking_paused,
            'created_at'      => $event->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data += [
                'checked_in'   => $event->checkedInCount(),
                'published_at' => $event->published_at?->toIso8601String(),
                'closed_at'    => $event->closed_at?->toIso8601String(),
                'cancelled_at' => $event->cancelled_at?->toIso8601String(),
                'cancellation_reason' => $event->cancellation_reason,
            ];
        }

        return $data;
    }

    public function index(): JsonResponse
    {
        $events = Event::with(['status', 'creator'])
            ->orderByDesc('start_datetime')
            ->get()
            ->map(fn($event) => $this->eventToArray($event));

        return response()->json(['events' => $events]);
    }

    /**
     * 🎯 publicIndex - الفعاليات المنشورة للجمهور
     * (مُصحَّح: حساب المتاح بدون is_vip_reserved)
     */
    public function publicIndex(): JsonResponse
    {
        $publishedStatus = Status::where('name', Status::PUBLISHED)->first();
        if (!$publishedStatus) {
            return response()->json(['events' => []]);
        }

        $totalSeats = Seat::count();   // ✨ الـ 997 كلها

        $events = Event::with(['status'])
            ->where('status_id', $publishedStatus->id)
            ->where('end_datetime', '>=', now())
            ->orderBy('start_datetime', 'asc')
            ->get()
            ->map(function ($event) use ($totalSeats) {
                // ✨ جديد: حساب المحجوز كوفد + المحجوز من الجمهور بشكل منفصل
                $vipBooked = Reservation::where('event_id', $event->id)
                    ->where('type', 'vip_guest')
                    ->where('status', '!=', 'cancelled')
                    ->count();

                $publicReserved = Reservation::where('event_id', $event->id)
                    ->where('type', '!=', 'vip_guest')
                    ->where('status', '!=', 'cancelled')
                    ->count();

                // المتاح للجمهور = الإجمالي - الوفود - الجمهور المحجوز
                $availableForPublic = $totalSeats - $vipBooked - $publicReserved;

                return [
                    'id'              => $event->id,
                    'uuid'            => $event->uuid,
                    'title'           => $event->title,
                    'description'     => $event->description,
                    'start_datetime'  => $event->start_datetime?->toIso8601String(),
                    'end_datetime'    => $event->end_datetime?->toIso8601String(),
                    'event_date'      => $event->start_datetime?->format('Y-m-d'),
                    'event_time'      => $event->start_datetime?->format('H:i'),
                    'total_seats'     => $totalSeats,
                    'vip_seats'       => $vipBooked,           // ✨ متغير per-event
                    'public_reserved' => $publicReserved,
                    'available'       => $availableForPublic,
                    'is_booking_paused' => (bool) $event->is_booking_paused,
                ];
            });

        return response()->json(['events' => $events]);
    }

    public function show($id): JsonResponse
    {
        $event = Event::with(['status', 'creator'])->findOrFail($id);

        return response()->json([
            'event' => $this->eventToArray($event, detailed: true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'start_datetime' => 'required|date|after:now',
            'end_datetime'   => 'required|date|after:start_datetime',
        ]);

        $draftStatus = Status::where('name', Status::DRAFT)->first();
        if (!$draftStatus) {
            return response()->json(['message' => 'حالة draft غير موجودة'], 500);
        }

        $event = Event::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'start_datetime' => $request->start_datetime,
            'end_datetime'   => $request->end_datetime,
            'status_id'      => $draftStatus->id,
            'created_by'     => $request->user()->id,
        ]);

        EventLog::create([
            'event_id'      => $event->id,
            'user_id'       => $request->user()->id,
            'old_status_id' => null,
            'new_status_id' => $draftStatus->id,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الفعالية بنجاح',
            'event'   => $this->eventToArray($event->fresh(['status', 'creator']), detailed: true),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('update', $event);

        $request->validate([
            'title'          => 'sometimes|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'start_datetime' => 'sometimes|date',
            'end_datetime'   => 'sometimes|date',
        ]);

        $dateChanged = false;
        if ($request->has('start_datetime')) {
            $newStart = \Carbon\Carbon::parse($request->start_datetime);
            $dateChanged = !$newStart->equalTo($event->start_datetime);
        }

        $event->update($request->only(['title', 'description', 'start_datetime', 'end_datetime']));

        if ($dateChanged) {
            $this->cancelReservationsAndNotify($event, 'تم تغيير موعد الفعالية');
        }

        return response()->json([
            'message'      => 'تم تعديل الفعالية بنجاح',
            'date_changed' => $dateChanged,
            'event'        => $this->eventToArray($event->fresh(['status', 'creator']), detailed: true),
        ]);
    }

    public function changeStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|exists:statuses,name',
        ]);

        $event = Event::findOrFail($id);

        $newStatusName = $request->status;
        $abilityMap = [
            Status::PUBLISHED => 'publish',
            Status::CLOSED    => 'close',
            Status::CANCELLED => 'cancel',
        ];
        $ability = $abilityMap[$newStatusName] ?? 'update';
        $this->authorize($ability, $event);

        $newStatus = Status::where('name', $newStatusName)->first();
        if (!$newStatus) {
            return response()->json(['message' => 'الحالة غير موجودة'], 422);
        }

        $oldStatus   = $event->status;
        $oldStatusId = $event->status_id;

        $event->status_id = $newStatus->id;

        if ($newStatus->name === Status::PUBLISHED) {
            if ($event->end_datetime?->isPast()) {
                return response()->json(['message' => 'لا يمكن نشر فعالية انتهى وقتها'], 422);
            }
            $event->published_at = now();
        }

        if ($newStatus->name === Status::CLOSED) {
            $event->closed_at = now();
        }

        if ($newStatus->name === Status::CANCELLED) {
            $event->cancelled_at = now();
            $this->cancelReservationsAndNotify($event, 'تم إلغاء الفعالية');
        }

        $event->save();

        EventLog::create([
            'event_id'      => $event->id,
            'user_id'       => $request->user()->id,
            'old_status_id' => $oldStatusId,
            'new_status_id' => $newStatus->id,
        ]);

        return response()->json([
            'message'    => 'تم تغيير الحالة بنجاح',
            'old_status' => $oldStatus?->display_name,
            'new_status' => $newStatus->display_name,
            'event'      => $this->eventToArray($event->fresh(['status', 'creator']), detailed: true),
        ]);
    }

    public function reserveVip(Request $request, $id): JsonResponse
    {
        $request->validate([
            'seat_ids'   => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        $event = Event::findOrFail($id);
        $this->authorize('manageVipSeats', $event);

        $publishedStatus = Status::where('name', Status::PUBLISHED)->first();
        if ($publishedStatus && $event->status_id === $publishedStatus->id) {
            return response()->json(['message' => 'لا يمكن حجز مقاعد وفود بعد النشر'], 422);
        }

        $booked = [];
        $failed = [];

        DB::transaction(function () use ($request, $event, &$booked, &$failed) {
            foreach ($request->seat_ids as $seatId) {
                $seat = Seat::lockForUpdate()->find($seatId);
                if (!$seat) {
                    $failed[] = "ID#{$seatId}";
                    continue;
                }

                if ($seat->isReservedForEvent($event->id)) {
                    $failed[] = $seat->label;
                    continue;
                }

                Reservation::create([
                    'user_id'  => $request->user()->id,
                    'event_id' => $event->id,
                    'seat_id'  => $seatId,
                    'status'   => 'confirmed',
                    'type'     => 'vip_guest',
                ]);

                $booked[] = $seat->label;
            }
        });

        return response()->json([
            'message' => 'تم حجز مقاعد الوفود',
            'booked'  => $booked,
            'failed'  => $failed,
        ]);
    }

    public function logs($id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $logs = EventLog::where('event_id', $id)
            ->with(['user', 'oldStatus', 'newStatus'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($log) => [
                'id'         => $log->id,
                'user'       => $log->user?->name ?? 'النظام',
                'old_status' => $log->oldStatus?->display_name ?? 'جديدة',
                'new_status' => $log->newStatus?->display_name,
                'date'       => $log->created_at?->toIso8601String(),
            ]);

        return response()->json(['logs' => $logs]);
    }

    private function cancelReservationsAndNotify(Event $event, string $reason): void
    {
        $reservations = $event->reservations()
            ->with('seat')
            ->where('status', 'confirmed')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->cancel();
        }

        if ($reservations->isNotEmpty()) {
            app(NotificationService::class)->notifyEventCancelled($event, $reason);
        }
    }
}
