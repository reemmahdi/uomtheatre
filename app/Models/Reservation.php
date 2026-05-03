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
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

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

    public function checkIn()
    {
        $this->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function ticketData(): array
    {
        return [
            'reservation_id' => $this->id,
            'event_title'     => $this->event->title,
            'event_date'      => $this->event->event_date,
            'section'         => $this->seat->section->name,
            'is_vip'          => $this->seat->section->is_vip,
            'row'             => $this->seat->row_number,
            'seat'            => $this->seat->seat_number,
            'label'           => $this->seat->label,
            'qr_code'         => $this->qr_code,
            'status'          => $this->status,
            'type'            => $this->type,
            'user_name'       => $this->user->name,
        ];
    }
}
