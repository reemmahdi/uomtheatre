<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * EventSeatAvailability Model — UOMTheatre
 * ════════════════════════════════════════════════════════════
 *
 * يمثّل حالة إتاحة مقعد واحد للحجز العام (عبر تطبيق الجمهور)
 * في فعالية معيّنة.
 *
 * منطق العمل:
 *   - is_public_available = true  → الجمهور يستطيع حجزه
 *   - is_public_available = false → يظهر محجوزاً في التطبيق
 *
 * ✨ يستخدم HasUuid Trait للحماية ضد IDOR
 *
 * ════════════════════════════════════════════════════════════
 */
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

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
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

    // ════════════════════════════════════════════════════════
    // Scopes
    // ════════════════════════════════════════════════════════

    /**
     * المقاعد المتاحة للجمهور
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_public_available', true);
    }

    /**
     * المقاعد المستبعدة من الجمهور
     */
    public function scopeExcluded($query)
    {
        return $query->where('is_public_available', false);
    }

    /**
     * مقاعد فعالية معيّنة
     */
    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
