<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 * ReservationController — UOMTheatre API (إعادة هندسة)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 التغيير المعماري:
 *   - حذف فحص is_vip_reserved (مقاعد الوفود تحدد per-event)
 *   - حذف فحص event_seat_availability (deprecated)
 *   - الفحص الوحيد: هل المقعد محجوز (vip_guest أو regular)؟
 *
 * ════════════════════════════════════════════════════════════════
 */
class ReservationController extends Controller
{
    public function myReservations(Request $request): JsonResponse
    {
        $reservations = Reservation::with(['event.status', 'seat.section'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($res) => [
                'id'             => $res->id,
                'event'          => $res->event?->title,
                'start_datetime' => $res->event?->start_datetime?->toIso8601String(),
                'event_date'     => $res->event?->start_datetime?->format('Y-m-d'),
                'event_time'     => $res->event?->start_datetime?->format('H:i'),
                'section'        => $res->seat?->section?->name,
                'label'          => $res->seat?->label,
                'status'         => $res->status,
                'type'           => $res->type,
                'qr_code'        => $res->qr_code,
                'created_at'     => $res->created_at?->toIso8601String(),
            ]);

        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'seat_id'  => 'required|exists:seats,id',
        ]);

        try {
            $reservation = DB::transaction(function () use ($request) {
                $event = Event::lockForUpdate()->findOrFail($request->event_id);
                $seat  = Seat::lockForUpdate()->findOrFail($request->seat_id);

                // ────────────────────────────────────────
                // فحوصات الفعالية
                // ────────────────────────────────────────
                if (!$event->isPublished()) {
                    throw new \RuntimeException('الفعالية غير متاحة للحجز');
                }

                if ($event->is_booking_paused) {
                    throw new \RuntimeException('الحجز موقوف مؤقتاً لهذه الفعالية');
                }

                if ($event->end_datetime?->isPast()) {
                    throw new \RuntimeException('انتهت هذه الفعالية');
                }

                // ────────────────────────────────────────
                // 🎯 الفحص الوحيد للمقعد: هل محجوز؟
                // (يشمل vip_guest و regular)
                // ────────────────────────────────────────
                $existingReservation = Reservation::where('event_id', $event->id)
                    ->where('seat_id', $seat->id)
                    ->where('status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->first();

                if ($existingReservation) {
                    if ($existingReservation->type === 'vip_guest') {
                        throw new \RuntimeException('هذا المقعد محجوز للوفود');
                    }
                    throw new \RuntimeException('هذا المقعد محجوز، اختاري مقعداً آخر');
                }

                // ────────────────────────────────────────
                // فحص حجز المستخدم لنفس الفعالية مسبقاً
                // ────────────────────────────────────────
                $userReservation = Reservation::where('user_id', $request->user()->id)
                    ->where('event_id', $request->event_id)
                    ->where('status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->first();

                if ($userReservation) {
                    throw new \RuntimeException('عندك حجز مسبق في هذه الفعالية');
                }

                return Reservation::create([
                    'user_id'  => $request->user()->id,
                    'event_id' => $event->id,
                    'seat_id'  => $seat->id,
                    'status'   => 'confirmed',
                    'type'     => 'regular',
                ]);
            });

            return response()->json([
                'message'     => 'تم الحجز بنجاح',
                'reservation' => $reservation->ticketData(),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل الحجز',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function ticket($id, Request $request): JsonResponse
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['ticket' => $reservation->ticketData()]);
    }

    public function cancel($id, Request $request): JsonResponse
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        $reservation->cancel();

        return response()->json(['message' => 'تم إلغاء الحجز بنجاح']);
    }
}
