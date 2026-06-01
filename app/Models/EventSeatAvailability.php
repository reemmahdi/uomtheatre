<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class EventSeatAvailability extends Model
{
    use HasUuid;

    protected $table = 'event_seat_availability';

    protected $fillable = [
        'uuid',
        'event_id',
        'seat_id',
        'is_public_available',
        'exclusion_reason',
        'updated_by',
    ];

    protected $casts = [
        'is_public_available' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_public_available', true);
    }

    public function scopeExcluded($query)
    {
        return $query->where('is_public_available', false);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
