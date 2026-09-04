<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if (!$reservation->checkIn($request->user()->id)) {
            return response()->json(['message' => 'تعذر تسجيل الحضور — الحجز ليس بحالة مؤكدة'], 409);
        }

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'data'    => [

                'name'    => $reservation->user?->name ?? $reservation->guest_name ?? 'ضيف',
                'event'   => $reservation->event?->title ?? '—',
                'section' => $reservation->seat?->section?->name ?? '—',
                'seat'    => $reservation->seat?->label ?? '—',
                'type'    => $reservation->type,
            ],
        ]);
    }
}
