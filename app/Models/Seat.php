<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{


    protected $fillable = ['section_id', 'row_number', 'seat_number', 'label', 'is_vip_reserved'];

    protected $casts = [
        'is_vip_reserved' => 'boolean',
    ];

    // المقعد تابع لقسم
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // المقعد عنده عدة حجوزات (في فعاليات مختلفة)
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function isReservedForEvent($eventId): bool
    {
        return $this->reservations()
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    public function statusForEvent($eventId): string
    {
        $reservation = $this->reservations()
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (!$reservation) {
            // إذا المقعد محجوز للوفود ولحد هسع ما انحجز
            if ($this->is_vip_reserved) {
                return 'vip_reserved';  // مقعد وفود غير محجوز ⬜
            }
            return 'available';         // متاح 🟢
        }
        if ($reservation->type === 'vip_guest') {
            return 'vip_guest';         // وفود محجوز ⬜
        }
        if ($reservation->status === 'checked_in') {
            return 'checked_in';        // حضر ✅
        }
        return 'reserved';              // محجوز 🔴
    }

}
