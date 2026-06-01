<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'is_vip', 'total_seats', 'total_rows'];

    protected $casts = [
        'is_vip' => 'boolean',
    ];

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function reservedSeatsForEvent($eventId): int
    {
        return $this->seats()
            ->whereHas('reservations', function ($query) use ($eventId) {
                $query->where('event_id', $eventId)
                      ->where('status', '!=', 'cancelled');
            })
            ->count();
    }

    public function vipBookedSeatsForEvent($eventId): int
    {
        return $this->seats()
            ->whereHas('reservations', function ($query) use ($eventId) {
                $query->where('event_id', $eventId)
                      ->where('type', 'vip_guest')
                      ->where('status', '!=', 'cancelled');
            })
            ->count();
    }

    public function availableSeatsForEvent($eventId): int
    {
        $reservedCount = $this->reservedSeatsForEvent($eventId);
        return $this->total_seats - $reservedCount;
    }

    public function publicAvailableSeatsForEvent($eventId): int
    {
        return $this->availableSeatsForEvent($eventId);
    }
}
