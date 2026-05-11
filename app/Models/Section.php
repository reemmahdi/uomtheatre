<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Section Model — UOMTheatre (إعادة هندسة)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التغيير المعماري:
 *   - is_vip column: يبقى في DB لكن يُتجاهل (legacy)
 *   - الحساب يعتمد على reservations فقط
 *
 * ════════════════════════════════════════════════════════════
 */
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

    /**
     * كم مقعد محجوز فعلياً في فعالية معيّنة؟ (وفود + جمهور)
     */
    public function reservedSeatsForEvent($eventId): int
    {
        return $this->seats()
            ->whereHas('reservations', function ($query) use ($eventId) {
                $query->where('event_id', $eventId)
                      ->where('status', '!=', 'cancelled');
            })
            ->count();
    }

    /**
     * كم مقعد محجوز كوفد في فعالية معيّنة؟
     */
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

    /**
     * كم مقعد متاح في فعالية معينة؟ (للجمهور)
     *
     * ✨ الـ logic الجديد: total - all_reservations (vip + regular)
     */
    public function availableSeatsForEvent($eventId): int
    {
        $reservedCount = $this->reservedSeatsForEvent($eventId);
        return $this->total_seats - $reservedCount;
    }

    /**
     * alias للـ backwards compatibility
     */
    public function publicAvailableSeatsForEvent($eventId): int
    {
        return $this->availableSeatsForEvent($eventId);
    }
}
