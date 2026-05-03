<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use App\Models\Seat;
use App\Models\Reservation;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with(['status', 'creator'])
            ->orderBy('event_date', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id'              => $event->id,
                    'title'           => $event->title,
                    'description'     => $event->description,
                    'event_date'      => $event->event_date,
                    'event_time'      => $event->event_time,
                    'status'          => $event->status->display_name,
                    'status_name'     => $event->status->name,
                    'created_by'      => $event->creator->name,
                    'reserved_seats'  => $event->reservedSeatsCount(),
                    'available_seats' => $event->availableSeatsCount(),
                    'occupancy_rate'  => $event->occupancyRate(),
                    'created_at'      => $event->created_at,
                ];
            });

        return response()->json(['events' => $events]);
    }

public function publicIndex()
    {
        $publishedStatus = \App\Models\Status::where('name', 'published')->first();
        if (!$publishedStatus) return response()->json([]);

        $events = \App\Models\Event::where('status_id', $publishedStatus->id)
            ->orderBy('event_date', 'asc')
            ->get()
            ->map(function ($event) {
                $totalSeats = \App\Models\Seat::where('is_vip_reserved', false)->count();
                $booked = \App\Models\Reservation::where('event_id', $event->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('type', '!=', 'vip_guest')
                    ->count();

                return [
                    'id'          => $event->id,
                    'title'       => $event->title,
                    'description' => $event->description,
                    'event_date'  => $event->event_date->format('Y-m-d'),
                    'event_time'  => $event->event_time,
                    'total_seats' => $totalSeats,
                    'booked'      => $booked,
                    'available'   => $totalSeats - $booked,
                ];
            });

        return response()->json($events);
    }

    public function show($id)
    {
        $event = Event::with(['status', 'creator'])->findOrFail($id);

        return response()->json([
            'event' => [
                'id'              => $event->id,
                'title'           => $event->title,
                'description'     => $event->description,
                'event_date'      => $event->event_date,
                'event_time'      => $event->event_time,
                'status'          => $event->status->display_name,
                'status_name'     => $event->status->name,
                'created_by'      => $event->creator->name,
                'reserved_seats'  => $event->reservedSeatsCount(),
                'available_seats' => $event->availableSeatsCount(),
                'occupancy_rate'  => $event->occupancyRate(),
                'checked_in'      => $event->checkedInCount(),
                'published_at'    => $event->published_at,
                'closed_at'       => $event->closed_at,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date'  => 'required|date|after:today',
            'event_time'  => 'nullable|string',
        ]);

        $draftStatus = Status::where('name', Status::DRAFT)->first();

        $event = Event::create([
            'title'       => $request->title,
            'description' => $request->description,
            'event_date'  => $request->event_date,
            'event_time'  => $request->event_time,
            'status_id'   => $draftStatus->id,
            'created_by'  => $request->user()->id,
        ]);

        EventLog::create([
            'event_id'      => $event->id,
            'user_id'       => $request->user()->id,
            'old_status_id' => null,
            'new_status_id' => $draftStatus->id,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الفعالية بنجاح',
            'event'   => $event->load('status'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'event_date'  => 'sometimes|date',
            'event_time'  => 'nullable|string',
        ]);

        $dateChanged = $request->has('event_date') && $request->event_date != $event->event_date->format('Y-m-d');

        $event->update($request->only(['title', 'description', 'event_date', 'event_time']));

        if ($dateChanged) {
            $this->cancelReservationsAndNotify($event, 'تم تغيير موعد الفعالية');
        }

        return response()->json([
            'message'      => 'تم تعديل الفعالية بنجاح',
            'date_changed' => $dateChanged,
            'event'        => $event->load('status'),
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|exists:statuses,name',
        ]);

        $event = Event::findOrFail($id);
        $oldStatus = $event->status;
        $newStatus = Status::where('name', $request->status)->first();

        $oldStatusId = $event->status_id;

        $event->status_id = $newStatus->id;

        if ($newStatus->name === Status::PUBLISHED) {
            $event->published_at = now();
        }

        if ($newStatus->name === Status::CLOSED) {
            $event->closed_at = now();
        }

        if ($newStatus->name === Status::CANCELLED) {
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
            'old_status' => $oldStatus->display_name,
            'new_status' => $newStatus->display_name,
            'event'      => $event->load('status'),
        ]);
    }

    public function reserveVip(Request $request, $id)
    {
        $request->validate([
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        $event = Event::findOrFail($id);

        $publishedStatus = Status::where('name', Status::PUBLISHED)->first();
        if ($event->status_id === $publishedStatus->id) {
            return response()->json([
                'message' => 'لا يمكن حجز مقاعد وفود بعد النشر',
            ], 422);
        }

        $booked = [];
        $failed = [];

        DB::transaction(function () use ($request, $event, &$booked, &$failed) {
            foreach ($request->seat_ids as $seatId) {
                $seat = Seat::find($seatId);

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

    public function logs($id)
    {
        $event = Event::findOrFail($id);

        $logs = EventLog::where('event_id', $id)
            ->with(['user', 'oldStatus', 'newStatus'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id'         => $log->id,
                    'user'       => $log->user->name,
                    'old_status' => $log->oldStatus ? $log->oldStatus->display_name : 'جديدة',
                    'new_status' => $log->newStatus->display_name,
                    'date'       => $log->created_at,
                ];
            });

        return response()->json(['logs' => $logs]);
    }

    private function cancelReservationsAndNotify(Event $event, string $reason)
    {
        $reservations = $event->reservations()
            ->where('status', 'confirmed')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->cancel();

            Notification::create([
                'user_id'  => $reservation->user_id,
                'title'    => $reason,
                'message'  => $reason . ': ' . $event->title . '. تم إلغاء حجزك للمقعد ' . $reservation->seat->label,
                'type'     => 'event_update',
                'event_id' => $event->id,
            ]);
        }
    }
}
