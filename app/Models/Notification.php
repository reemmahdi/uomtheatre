<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'event_id',
        'is_read',
        'reservation_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public const TYPE_GENERAL          = 'general';
    public const TYPE_APPROVAL_REQUEST = 'approval_request';
    public const TYPE_EVENT_APPROVED   = 'event_approved';
    public const TYPE_EVENT_REJECTED   = 'event_rejected';
    public const TYPE_EVENT_PUBLISHED  = 'event_published';
    public const TYPE_EVENT_CANCELLED  = 'event_cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update(['is_read' => false]);
        }
    }
}
