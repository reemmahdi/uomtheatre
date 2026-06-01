<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSeatAvailability;
use App\Models\Seat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventSeatAvailabilityService
{
    public function initializeForEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            $existingCount = EventSeatAvailability::where('event_id', $event->id)
                ->lockForUpdate()
                ->count();

            if ($existingCount > 0) {
                return 0;
            }

            $now = now();
            $userId = Auth::id();

            $seats = Seat::orderBy('id')->get();

            $records = [];
            foreach ($seats as $seat) {
                $records[] = [
                    'uuid'                => (string) Str::uuid(),
                    'event_id'            => $event->id,
                    'seat_id'             => $seat->id,

                    'is_public_available' => true,
                    'exclusion_reason'    => null,
                    'updated_by'          => $userId,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }

            DB::table('event_seat_availability')->insert($records);

            return count($records);
        });
    }

    public function toggleSeat(Event $event, int $seatId, ?string $reason = null): EventSeatAvailability
    {
        $record = EventSeatAvailability::firstOrCreate(
            [
                'event_id' => $event->id,
                'seat_id'  => $seatId,
            ],
            [
                'is_public_available' => true,
                'updated_by'          => Auth::id(),
            ]
        );

        $newAvailability = !$record->is_public_available;

        $record->update([
            'is_public_available' => $newAvailability,

            'exclusion_reason'    => $newAvailability ? null : $reason,
            'updated_by'          => Auth::id(),
        ]);

        return $record;
    }

    public function bulkUpdate(Event $event, array $seatIds, bool $available, ?string $reason = null): int
    {
        if (empty($seatIds)) {
            return 0;
        }

        $userId = Auth::id();
        $count  = 0;

        DB::transaction(function () use ($event, $seatIds, $available, $reason, $userId, &$count) {
            foreach ($seatIds as $seatId) {
                EventSeatAvailability::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'seat_id'  => (int) $seatId,
                    ],
                    [

                        'is_public_available' => $available,
                        'exclusion_reason'    => $available ? null : $reason,
                        'updated_by'          => $userId,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    public function makeAllAvailable(Event $event): int
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->update([
                'is_public_available' => true,
                'exclusion_reason'    => null,
                'updated_by'          => Auth::id(),
                'updated_at'          => now(),
            ]);
    }

    public function excludeSection(Event $event, int $sectionId, ?string $reason = null): int
    {
        $seatIds = Seat::where('section_id', $sectionId)->pluck('id')->toArray();
        return $this->bulkUpdate($event, $seatIds, false, $reason);
    }

    public function includeSection(Event $event, int $sectionId): int
    {
        $seatIds = Seat::where('section_id', $sectionId)->pluck('id')->toArray();
        return $this->bulkUpdate($event, $seatIds, true);
    }

    public function getAvailableSeatIds(Event $event): array
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->where('is_public_available', true)
            ->pluck('seat_id')
            ->toArray();
    }

    public function getExcludedSeatIds(Event $event): array
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->where('is_public_available', false)
            ->pluck('seat_id')
            ->toArray();
    }

    public function getStats(Event $event): array
    {
        $stats = EventSeatAvailability::where('event_id', $event->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_public_available = ? THEN 1 ELSE 0 END) as available
            ', [true])
            ->first();

        $total     = (int) ($stats->total ?? 0);
        $available = (int) ($stats->available ?? 0);

        return [
            'total'     => $total,
            'available' => $available,
            'excluded'  => $total - $available,
        ];
    }

    public function isInitialized(Event $event): bool
    {
        return EventSeatAvailability::where('event_id', $event->id)->exists();
    }
}
