<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class EventApproval extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'event_id',
        'round_number',
        'status',
        'rejection_reason',
        'user_id',
        'role_id',
    ];

    protected $casts = [
        'round_number' => 'integer',
    ];

    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function hasRejectionReason(): bool
    {
        return $this->isRejected() && !empty($this->rejection_reason);
    }
}
