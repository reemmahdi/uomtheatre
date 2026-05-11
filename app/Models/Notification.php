<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Notification Model — UOMTheatre (مُحدّث - إصلاح Claude)
 * ════════════════════════════════════════════════════════════
 *
 * ✨ التعديل في هذه النسخة:
 *   - تحديث اسم الجدول: notifications → app_notifications
 *     (لتجنب التعارض مع Laravel Notifiable trait)
 *   - إضافة constants لأنواع الإشعارات (للأمان)
 *   - scope إضافي: forUser() و recent()
 *
 * ════════════════════════════════════════════════════════════
 */
class Notification extends Model
{
    /**
     * ✨ تم تغيير الاسم لتجنّب التعارض مع Laravel Notifiable
     */
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'event_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ════════════════════════════════════════════════════════
    // Constants لأنواع الإشعارات
    // (استخدمي هذه بدل string literals للأمان)
    // ════════════════════════════════════════════════════════
    public const TYPE_GENERAL          = 'general';
    public const TYPE_APPROVAL_REQUEST = 'approval_request';   // طلب موافقة
    public const TYPE_EVENT_APPROVED   = 'event_approved';     // فعالية تمت الموافقة
    public const TYPE_EVENT_REJECTED   = 'event_rejected';     // فعالية مرفوضة
    public const TYPE_EVENT_PUBLISHED  = 'event_published';    // فعالية منشورة
    public const TYPE_EVENT_CANCELLED  = 'event_cancelled';    // فعالية ملغاة

    // ════════════════════════════════════════════════════════
    // Relationships
    // ════════════════════════════════════════════════════════
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // ════════════════════════════════════════════════════════
    // Scopes
    // ════════════════════════════════════════════════════════
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * ✨ جديد: إشعارات مستخدم معيّن
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * ✨ جديد: الإشعارات الأحدث أولاً (مفيد للجرس)
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    // ════════════════════════════════════════════════════════
    // Actions
    // ════════════════════════════════════════════════════════
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
