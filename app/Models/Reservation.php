<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'seat_id',
        'status',
        'type',
        'qr_code',
        'checked_in_at',
        'guest_name',
        'guest_phone',
        'schedule_change_id',
        'confirm_until',
        'change_confirmed_at',

    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'confirm_until'       => 'datetime',
            'change_confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($reservation) {
            if (!$reservation->qr_code) {
                $reservation->qr_code = 'UOM-' . strtoupper(Str::random(8)) . '-' . time();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function checkIn(): void
    {
        $this->update([
            'status'        => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function ticketData(): array
    {
        return [
            'reservation_id' => $this->id,
            'event_title'    => $this->event?->title,
            'event_start'    => $this->event?->start_datetime,
            'event_end'      => $this->event?->end_datetime,
            'event_date'     => $this->event?->start_datetime?->format('Y-m-d H:i'),
            'section'        => $this->seat?->section?->name,
            'is_vip'         => (bool) ($this->seat?->section?->is_vip ?? false),
            'row'            => $this->seat?->row_number,
            'seat'           => $this->seat?->seat_number,
            'label'          => $this->seat?->label,
            'qr_code'        => $this->qr_code,
            'status'         => $this->status,
            'type'           => $this->type,
            'user_name'      => $this->user?->name ?? $this->guest_name,
        ];
    }
}
