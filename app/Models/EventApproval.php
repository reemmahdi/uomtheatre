<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * EventApproval Model — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التصميم الجديد:
 *   - موافق واحد فقط (مكتب رئاسة الجامعة)
 *   - مدير المسرح صار "مشاهد فقط" بدون موافقة/رفض
 *   - السجل ينشأ فقط عند اتخاذ القرار (لا توجد حالة pending)
 *   - دعم دورات إعادة الإرسال عبر round_number
 *
 * 📊 البنية:
 *   - id, uuid, event_id, round_number, status, rejection_reason
 *   - timestamps (created_at + updated_at)
 *
 * 🔒 القيود:
 *   - UNIQUE(event_id, round_number) → قرار واحد لكل دورة
 *
 * 💡 ملاحظات:
 *   - لا نحفظ decided_by (الجهة معروفة: مكتب الرئاسة دائماً)
 *   - لا نحفظ decided_at (created_at يكفي)
 *   - rejection_reason اختياري (nullable)
 *
 * ════════════════════════════════════════════════════════════
 */
class EventApproval extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'event_id',
        'round_number',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'round_number' => 'integer',
    ];

    // ════════════════════════════════════════════════════════
    // Constants للحالات
    // ════════════════════════════════════════════════════════
    //
    // ملاحظة: لا توجد STATUS_PENDING في التصميم الجديد
    //         (السجل ينشأ فقط عند اتخاذ القرار)
    //
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // ════════════════════════════════════════════════════════
    // Helper Methods
    // ════════════════════════════════════════════════════════
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * هل لديها سبب رفض مكتوب؟
     */
    public function hasRejectionReason(): bool
    {
        return $this->isRejected() && !empty($this->rejection_reason);
    }
}
