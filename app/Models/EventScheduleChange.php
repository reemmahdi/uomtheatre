<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/// سجل تغييرات مواعيد الفعاليات — صف لكل تأجيل: القديم والجديد والسبب
class EventScheduleChange extends Model
{
    protected $fillable = [
        'event_id', 'old_start_datetime', 'old_end_datetime',
        'new_start_datetime', 'new_end_datetime', 'reason', 'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'old_start_datetime' => 'datetime',
            'old_end_datetime'   => 'datetime',
            'new_start_datetime' => 'datetime',
            'new_end_datetime'   => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}