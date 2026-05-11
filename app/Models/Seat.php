<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Seat Model — UOMTheatre (إعادة هندسة - مقاعد الوفود per-event)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التغيير المعماري:
 *   كل المقاعد متساوية (997). تصنيف الوفود يتم per-event من
 *   جدول reservations (type = 'vip_guest').
 *
 *   - is_vip_reserved column: يبقى في DB للـ backwards compatibility
 *     لكن يُتجاهل في الـ logic الجديد
 *   - event_seat_availability table: لم يعد مستخدماً (deprecated)
 *
 * ════════════════════════════════════════════════════════════
 */
class Seat extends Model
{
    protected $fillable = [
        'section_id',
        'row_number',
        'seat_number',
        'label',
        'is_vip_reserved',   // legacy - يُتجاهل
    ];

    protected $casts = [
        'is_vip_reserved' => 'boolean',
    ];

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // ════════════════════════════════════════════════════════
    // Helper Methods
    // ════════════════════════════════════════════════════════

    /**
     * هل هذا المقعد محجوز في فعالية معيّنة؟
     */
    public function isReservedForEvent($eventId): bool
    {
        return $this->reservations()
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * هل المقعد محجوز كوفد في هذه الفعالية؟
     */
    public function isVipBookedForEvent($eventId): bool
    {
        return $this->reservations()
            ->where('event_id', $eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * 🎯 الـ logic الجديد: حالة المقعد لفعالية معيّنة
     *
     * الحالات الممكنة:
     *   - 'checked_in'  → الجمهور حضر فعلياً
     *   - 'reserved'    → محجوز من الجمهور (type=regular)
     *   - 'vip_guest'   → محجوز كوفد (type=vip_guest)
     *   - 'available'   → متاح للحجز
     *
     * ❌ حُذفت: 'excluded', 'vip_reserved' (legacy)
     */
    public function statusForEvent($eventId): string
    {
        $reservation = $this->reservations()
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($reservation) {
            if ($reservation->type === 'vip_guest') {
                return 'vip_guest';
            }
            if ($reservation->status === 'checked_in') {
                return 'checked_in';
            }
            return 'reserved';
        }

        return 'available';
    }

    /**
     * 🎯 الحجز الفعلي (لو موجود) - يُستخدم لاسترجاع بيانات الضيف
     */
    public function activeReservationForEvent($eventId): ?Reservation
    {
        return $this->reservations()
            ->where('event_id', $eventId)
            ->where('status', '!=', 'cancelled')
            ->first();
    }
}
