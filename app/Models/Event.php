<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasUuid;

    public const TOTAL_SEATS = 997;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'status_id',
        'created_by',
        'published_by',
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

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function approvals()
    {
        return $this->hasMany(EventApproval::class);
    }

    public function latestApproval()
    {
        return $this->hasOne(EventApproval::class)
            ->latestOfMany('round_number');
    }

    public function officeApproval()
    {
        return $this->latestApproval();
    }

    public function seatAvailability()
    {
        return $this->hasMany(EventSeatAvailability::class);
    }

    public function isDraft(): bool
    {
        return $this->status?->name === Status::DRAFT;
    }

    public function isRejected(): bool
    {
        return $this->status?->name === Status::REJECTED;
    }

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

    public function isPendingApproval(): bool
    {
        return $this->status?->name === Status::ADDED;
    }

    public function currentRound(): int
    {
        $maxRound = $this->approvals()->max('round_number');
        return $maxRound ? (int) $maxRound : 1;
    }

    public function isApprovedByOffice(): bool
    {
        $latest = $this->latestApproval;
        return $latest && $latest->isApproved();
    }

    public function hasAnyRejection(): bool
    {
        $latest = $this->latestApproval;
        return $latest && $latest->isRejected();
    }

    public function approvalsCount(): int
    {
        return $this->approvals()
            ->where('status', EventApproval::STATUS_APPROVED)
            ->count();
    }

    public function isReadyToPublish(): bool
    {
        return $this->isActive() && $this->isApprovedByOffice();
    }

    public function hasBeenPublished(): bool
    {
        return $this->published_at !== null;
    }

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
