<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasUuid;  // ✨ جديد: توليد UUID تلقائياً + Route Model Binding

    protected $fillable = [
        'uuid',  // ✨ جديد: للحماية ضد IDOR
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

    // ==================== Relationships ====================
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

    // ==================== Status Checks ====================
    public function isDraft(): bool
    {
        return $this->status->name === Status::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status->name === Status::PUBLISHED;
    }

    /**
     * هل يمكن الحجز لهذه الفعالية؟
     *
     * شرطان: 1) الفعالية منشورة  2) الحجوزات غير موقوفة مؤقتاً
     */
    public function isBookable(): bool
    {
        return $this->status->name === Status::PUBLISHED
            && !$this->is_booking_paused;
    }

    public function isCancelled(): bool
    {
        return $this->status->name === 'cancelled';
    }

    /**
     * ✨ هل الحجوزات موقوفة مؤقتاً؟
     */
    public function isBookingPaused(): bool
    {
        return (bool) $this->is_booking_paused;
    }

    // ==================== Datetime Helpers ====================
    public function isOngoing(): bool
    {
        $now = now();
        return $now->between($this->start_datetime, $this->end_datetime);
    }

    public function isUpcoming(): bool
    {
        return $this->start_datetime->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->end_datetime->isPast();
    }

    public function durationInMinutes(): int
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    // ==================== Seat Statistics ====================
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
        return 945 - $this->reservedSeatsCount();
    }

    public function occupancyRate(): float
    {
        return round(($this->reservedSeatsCount() / 945) * 100, 1);
    }
}
