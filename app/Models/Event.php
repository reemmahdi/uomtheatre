<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Event Model — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════
 *
 * 🎯 التغييرات الرئيسية:
 *
 *   ❌ حُذف:    theaterApproval() - مدير المسرح يشاهد فقط
 *   ❌ حُذف:    isApprovedByTheater()
 *   ❌ حُذف:    isFullyApproved() (لا معنى لها مع موافق واحد)
 *
 *   ✏️ تعديل:   officeApproval() → جلب القرار من آخر دورة
 *   ✏️ تعديل:   hasAnyRejection() → يفحص آخر دورة فقط
 *
 *   ➕ جديد:    currentRound() - رقم الدورة الحالية
 *   ➕ جديد:    latestApproval() - آخر قرار (لأي دورة)
 *   ➕ جديد:    isApproved() - وافقت الرئاسة بآخر دورة
 *   ➕ جديد:    isRejected() - رفضتها الرئاسة بآخر دورة
 *   ➕ جديد:    publisher() - علاقة مع مين نشر
 *
 * 🔄 منطق الحالة الجديد:
 *
 *   draft → added → [rejected (round++) | active → published → closed]
 *                                                            ↓
 *                                                        cancelled
 *
 *   - active = وافقت الرئاسة، تنتظر زر "نشر" من مدير الإعلام
 *   - published = ضغط مدير الإعلام "نشر" → متاحة للجمهور
 *
 * ════════════════════════════════════════════════════════════
 */
class Event extends Model
{
    use HasUuid;

    /**
     * ثابت لعدد المقاعد الكلّي في قاعة الدكتور محمود الجليلي
     */
    public const TOTAL_SEATS = 997;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'status_id',
        'created_by',
        'published_by',          // ✨ جديد - مين ضغط زر النشر
        'published_at',
        'closed_at',
        'cancellation_reason',
        'cancelled_at',
        'is_booking_paused',
        'paused_at',
    ];

    protected $casts = [
        'start_datetime'    => 'datetime',
        'end_datetime'      => 'datetime',
        'published_at'      => 'datetime',
        'closed_at'         => 'datetime',
        'cancelled_at'      => 'datetime',
        'paused_at'         => 'datetime',
        'is_booking_paused' => 'boolean',
    ];

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ✨ جديد: مَن نشر الفعالية (مدير الإعلام عادة)
     */
    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function logs()
    {
        return $this->hasMany(EventLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * كل سجلات الموافقة (من كل الدورات)
     */
    public function approvals()
    {
        return $this->hasMany(EventApproval::class);
    }

    /**
     * ✨ جديد: آخر سجل موافقة (من أعلى دورة)
     *
     * كل فعالية لها قرار واحد لكل دورة، ونحن نحتاج آخر قرار
     * (سواء كان approved أو rejected) لمعرفة الحالة الحالية.
     */
    public function latestApproval()
    {
        return $this->hasOne(EventApproval::class)
            ->latestOfMany('round_number');
    }

    /**
     * ✏️ مُعدَّل: قرار مكتب الرئاسة (من آخر دورة)
     *
     * يحافظ على اسم الـ method القديم للتوافق مع باقي الكود،
     * لكن الـ logic مختلف: ما عاد فيه theater approval منفصلة.
     */
    public function officeApproval()
    {
        return $this->latestApproval();
    }

    public function seatAvailability()
    {
        return $this->hasMany(EventSeatAvailability::class);
    }

    // ════════════════════════════════════════════════════════
    // Status Checks (مع nullsafe operator)
    // ════════════════════════════════════════════════════════
    public function isDraft(): bool
    {
        return $this->status?->name === Status::DRAFT;
    }

    /**
     * ✨ جديد: هل الفعالية مرفوضة من مكتب الرئاسة؟
     */
    public function isRejected(): bool
    {
        return $this->status?->name === Status::REJECTED;
    }

    /**
     * ✨ جديد: هل الفعالية مقبولة وتنتظر النشر؟
     */
    public function isActive(): bool
    {
        return $this->status?->name === Status::ACTIVE;
    }

    public function isPublished(): bool
    {
        return $this->status?->name === Status::PUBLISHED;
    }

    public function isBookable(): bool
    {
        return $this->status?->name === Status::PUBLISHED
            && !$this->is_booking_paused;
    }

    public function isCancelled(): bool
    {
        return $this->status?->name === Status::CANCELLED;
    }

    public function isBookingPaused(): bool
    {
        return (bool) $this->is_booking_paused;
    }

    // ════════════════════════════════════════════════════════
    // Approval Helpers (مُبسَّطة - موافق واحد)
    // ════════════════════════════════════════════════════════

    /**
     * هل الفعالية بانتظار قرار مكتب الرئاسة؟
     */
    public function isPendingApproval(): bool
    {
        return $this->status?->name === Status::ADDED;
    }

    /**
     * ✨ جديد: رقم الدورة الحالية
     *
     * - فعالية جديدة لم تُرسل بعد: 1
     * - فعالية أُرسلت مرة واحدة: 1
     * - فعالية رُفضت ثم أُعيد إرسالها: 2
     * - وهكذا...
     */
    public function currentRound(): int
    {
        $maxRound = $this->approvals()->max('round_number');
        return $maxRound ? (int) $maxRound : 1;
    }

    /**
     * ✨ جديد: هل وافقت الرئاسة في آخر دورة؟
     */
    public function isApprovedByOffice(): bool
    {
        $latest = $this->latestApproval;
        return $latest && $latest->isApproved();
    }

    /**
     * هل الفعالية مرفوضة في آخر دورة؟
     */
    public function hasAnyRejection(): bool
    {
        $latest = $this->latestApproval;
        return $latest && $latest->isRejected();
    }

    /**
     * عدد الموافقات الناجحة (عبر كل الدورات)
     */
    public function approvalsCount(): int
    {
        return $this->approvals()
            ->where('status', EventApproval::STATUS_APPROVED)
            ->count();
    }

    // ════════════════════════════════════════════════════════
    // Publishing Helpers (✨ جديد)
    // ════════════════════════════════════════════════════════

    /**
     * هل الفعالية جاهزة للنشر؟
     * (موافقة من الرئاسة + بحالة active)
     */
    public function isReadyToPublish(): bool
    {
        return $this->isActive() && $this->isApprovedByOffice();
    }

    /**
     * هل تم نشر الفعالية فعلاً؟
     */
    public function hasBeenPublished(): bool
    {
        return $this->published_at !== null;
    }

    // ════════════════════════════════════════════════════════
    // Datetime Helpers
    // ════════════════════════════════════════════════════════
    public function isOngoing(): bool
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return false;
        }
        return now()->between($this->start_datetime, $this->end_datetime);
    }

    public function isUpcoming(): bool
    {
        return $this->start_datetime?->isFuture() ?? false;
    }

    public function hasEnded(): bool
    {
        return $this->end_datetime?->isPast() ?? false;
    }

    public function durationInMinutes(): int
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return 0;
        }
        return (int) abs($this->start_datetime->diffInMinutes($this->end_datetime));
    }

    // ════════════════════════════════════════════════════════
    // Seat Statistics
    // ════════════════════════════════════════════════════════
    public function reservedSeatsCount(): int
    {
        return $this->reservations()
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    public function checkedInCount(): int
    {
        return $this->reservations()
            ->where('status', 'checked_in')
            ->count();
    }

    public function availableSeatsCount(): int
    {
        return self::TOTAL_SEATS - $this->reservedSeatsCount();
    }

    public function occupancyRate(): float
    {
        if (self::TOTAL_SEATS <= 0) {
            return 0.0;
        }
        return round(($this->reservedSeatsCount() / self::TOTAL_SEATS) * 100, 1);
    }

    public function excludedSeatsCount(): int
    {
        return $this->seatAvailability()
            ->where('is_public_available', false)
            ->count();
    }

    public function publicAvailableSeatsCount(): int
    {
        return self::TOTAL_SEATS - $this->reservedSeatsCount() - $this->excludedSeatsCount();
    }
}
