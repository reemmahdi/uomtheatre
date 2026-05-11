<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ════════════════════════════════════════════════════════════════
 * CheckInController — UOMTheatre API (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 Policy authorize check
 *   🟡 nullsafe على relationships (vip_guest قد لا يكون له user)
 *
 * ════════════════════════════════════════════════════════════════
 */
class CheckInController extends Controller
{
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $reservation = Reservation::with(['user', 'event', 'seat.section'])
            ->where('qr_code', $request->qr_code)
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'رمز QR غير صالح'], 404);
        }

        // ✨ Policy check (موجود في ReservationPolicy::checkIn)
        $this->authorize('checkIn', $reservation);

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'هذا الحجز ملغي'], 422);
        }

        if ($reservation->status === 'checked_in') {
            return response()->json([
                'message'    => 'تم تسجيل الحضور مسبقاً',
                'checked_at' => $reservation->checked_in_at?->toIso8601String(),
            ], 422);
        }

        $reservation->checkIn();

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'data'    => [
                // ✨ nullsafe: vip_guest قد لا يكون له user (guest_name بدلاً منه)
                'name'    => $reservation->user?->name ?? $reservation->guest_name ?? 'ضيف',
                'event'   => $reservation->event?->title ?? '—',
                'section' => $reservation->seat?->section?->name ?? '—',
                'seat'    => $reservation->seat?->label ?? '—',
                'type'    => $reservation->type,
            ],
        ]);
    }
}
