<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function myReservations(Request $request)
    {
        $reservations = Reservation::with(['event.status', 'seat.section'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($res) {
                return [
                    'id'         => $res->id,
                    'event'      => $res->event->title,
                    'event_date' => $res->event->event_date,
                    'section'    => $res->seat->section->name,
                    'is_vip'     => $res->seat->section->is_vip,
                    'label'      => $res->seat->label,
                    'status'     => $res->status,
                    'type'       => $res->type,
                    'qr_code'    => $res->qr_code,
                    'created_at' => $res->created_at,
                ];
            });

        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'seat_id'  => 'required|exists:seats,id',
        ]);

        $event = Event::findOrFail($request->event_id);
        $seat = Seat::findOrFail($request->seat_id);

        if (!$event->isPublished()) {
            return response()->json(['message' => 'الفعالية غير متاحة للحجز'], 422);
        }

        if ($seat->is_vip_reserved) {
            return response()->json(['message' => 'هذا المقعد مخصص للوفود'], 422);
        }

        $existingReservation = Reservation::where('user_id', $request->user()->id)
            ->where('event_id', $request->event_id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingReservation) {
            return response()->json(['message' => 'عندك حجز مسبق في هذه الفعالية'], 422);
        }

        try {
            $reservation = DB::transaction(function () use ($request, $seat, $event) {
                $lockedSeat = Seat::lockForUpdate()->find($seat->id);

                if ($lockedSeat->isReservedForEvent($event->id)) {
                    throw new \Exception('المقعد محجوز');
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

        } catch (\Exception $e) {
            return response()->json(['message' => 'المقعد محجوز، اختر مقعد آخر'], 422);
        }
    }

    public function ticket($id, Request $request)
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['ticket' => $reservation->ticketData()]);
    }

    public function cancel($id, Request $request)
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        $reservation->cancel();

        return response()->json(['message' => 'تم إلغاء الحجز بنجاح']);
    }
}
