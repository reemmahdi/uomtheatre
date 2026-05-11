<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Services\EventApprovalService;
use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Event Model — UOMTheatre (مُحدّث للمرحلة 2.أ)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات في هذه النسخة (إصلاحات Claude):
 *   - إضافة علاقة seatAvailability() المفقودة (للمرحلة 2.أ)
 *   - تصحيح عدد المقاعد: 997 بدل 945 (لأن النظام صار بدون VIP ثابت)
 *   - استخدام nullsafe operator (?->) في كل status checks
 *   - حماية durationInMinutes() من القيم السالبة (Carbon 3)
 *   - ثابت TOTAL_SEATS لتسهيل التعديل لاحقاً
 *
 * ════════════════════════════════════════════════════════════
 */
class Event extends Model
{
    use HasUuid;

    /**
     * ✨ ثابت لعدد المقاعد الكلّي في القاعة
     * (قاعة الدكتور محمود الجليلي — جامعة الموصل)
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
     * علاقة الموافقات (المرحلة 1.ب)
     */
    public function approvals()
    {
        return $this->hasMany(EventApproval::class);
    }

    /**
     * موافقة مدير المسرح (واحدة فقط لكل فعالية)
     */
    public function theaterApproval()
    {
        return $this->hasOne(EventApproval::class)
            ->whereHas('role', fn($q) => $q->where('name', Role::THEATER_MANAGER));
    }

    /**
     * موافقة مكتب الرئيس (واحدة فقط لكل فعالية)
     */
    public function officeApproval()
    {
        return $this->hasOne(EventApproval::class)
            ->whereHas('role', fn($q) => $q->where('name', Role::UNIVERSITY_OFFICE));
    }

    /**
     * ✨ جديد (المرحلة 2.أ): علاقة إتاحة المقاعد للجمهور
     *
     * كل سجل = حالة إتاحة مقعد واحد لهذه الفعالية:
     *   - is_public_available = true  → الجمهور يستطيع حجزه
     *   - is_public_available = false → مستبعد (يظهر محجوزاً)
     */
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
    // Approval Helpers
    // ════════════════════════════════════════════════════════

    /**
     * هل الفعالية بانتظار الموافقات (added)؟
     */
    public function isPendingApproval(): bool
    {
        return $this->status?->name === Status::ADDED;
    }

    /**
     * هل وافق الجميع على الفعالية؟
     */
    public function isFullyApproved(): bool
    {
        return app(EventApprovalService::class)->areAllApprovalsComplete($this);
    }

    /**
     * هل وافق مدير المسرح؟
     */
    public function isApprovedByTheater(): bool
    {
        return $this->theaterApproval
            && $this->theaterApproval->isApproved();
    }

    /**
     * هل وافق مكتب الرئيس؟
     */
    public function isApprovedByOffice(): bool
    {
        return $this->officeApproval
            && $this->officeApproval->isApproved();
    }

    /**
     * هل تم رفض الفعالية من أي طرف؟
     */
    public function hasAnyRejection(): bool
    {
        return $this->approvals()
            ->where('status', EventApproval::STATUS_REJECTED)
            ->exists();
    }

    /**
     * عدد الموافقات المنجزة (من 2)
     */
    public function approvalsCount(): int
    {
        return $this->approvals()
            ->where('status', EventApproval::STATUS_APPROVED)
            ->count();
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

    /**
     * ✨ مُصحَّح: استخدام abs() لتجنّب القيم السالبة (Carbon 3 في Laravel 12)
     */
    public function durationInMinutes(): int
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return 0;
        }
        return (int) abs($this->start_datetime->diffInMinutes($this->end_datetime));
    }

    // ════════════════════════════════════════════════════════
    // Seat Statistics (مُصحَّحة — تستخدم 997 بدل 945)
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

    /**
     * ✨ مُصحَّح: استخدام TOTAL_SEATS (997) بدل 945 الثابت
     */
    public function availableSeatsCount(): int
    {
        return self::TOTAL_SEATS - $this->reservedSeatsCount();
    }

    /**
     * ✨ مُصحَّح: استخدام TOTAL_SEATS + حماية من القسمة على صفر
     */
    public function occupancyRate(): float
    {
        if (self::TOTAL_SEATS <= 0) {
            return 0.0;
        }
        return round(($this->reservedSeatsCount() / self::TOTAL_SEATS) * 100, 1);
    }

    /**
     * ✨ جديد: عدد المقاعد المستبعدة من الجمهور لهذه الفعالية
     * (مفيد للوحة التحكم - بطاقة "مقاعد الوفود")
     */
    public function excludedSeatsCount(): int
    {
        return $this->seatAvailability()
            ->where('is_public_available', false)
            ->count();
    }

    /**
     * ✨ جديد: عدد المقاعد المتاحة للجمهور فعلياً
     * (المتاحة = الكلّية - المحجوزة - المستبعدة)
     */
    public function publicAvailableSeatsCount(): int
    {
        return self::TOTAL_SEATS - $this->reservedSeatsCount() - $this->excludedSeatsCount();
    }
}
