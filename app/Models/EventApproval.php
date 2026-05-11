<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * EventApproval Model — UOMTheatre
 * ════════════════════════════════════════════════════════════
 *
 * يمثّل موافقة دور واحد على فعالية (موافقة جزئية)
 *
 * كل فعالية تحتاج موافقتين متوازيتين:
 *   - موافقة من theater_manager
 *   - موافقة من university_office
 *
 * عند موافقة الاثنين معاً → الفعالية تنتقل إلى active
 * عند رفض أي منهما → الفعالية تعود إلى draft
 *
 * ✨ يستخدم HasUuid Trait للحماية ضد IDOR
 *
 * ════════════════════════════════════════════════════════════
 */
class EventApproval extends Model
{
    use HasUuid;  // ✨ توليد UUID تلقائياً

    protected $fillable = [
        'uuid',
        'event_id',
        'user_id',
        'role_id',
        'status',
        'note',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════
    // Constants للحالات
    // ════════════════════════════════════════════════════════
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // ════════════════════════════════════════════════════════
    // Helper Methods
    // ════════════════════════════════════════════════════════
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
