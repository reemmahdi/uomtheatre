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
        'checked_in_by',
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
                $reservation->qr_code = self::generateQrCode();
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

    public function checkIn(?int $byUserId = null): bool
    {
        $updated = static::whereKey($this->id)
            ->where('status', 'confirmed')
            ->update([
                'status'        => 'checked_in',
                'checked_in_at' => now(),
                'checked_in_by' => $byUserId,
            ]);
        if ($updated === 1) {
            $this->refresh();
        }
        return $updated === 1;
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

    public static function generateQrCode(): string
    {
        do {
            $code = 'UOM-' . strtoupper(Str::random(24));
        } while (self::where('qr_code', $code)->exists());
        return $code;
    }
}
