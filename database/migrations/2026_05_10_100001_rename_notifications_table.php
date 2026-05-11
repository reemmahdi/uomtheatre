<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Rename notifications → app_notifications (إصلاح Claude)
 * ════════════════════════════════════════════════════════════════════
 *
 * المشكلة:
 *   - User Model يستخدم Laravel's Notifiable trait
 *   - Notifiable trait يتوقّع جدول 'notifications' بـ schema معيّن
 *     (uuid id, type, notifiable_type, notifiable_id, data, read_at...)
 *   - جدولك بـ schema مختلف تماماً
 *   - أي استدعاء لـ $user->notify(...) سيفشل
 *
 * ✅ الحل: إعادة تسمية الجدول إلى 'app_notifications'
 *    وتحديث Notification model: protected $table = 'app_notifications';
 *
 * ⚠️ ملاحظة مهمة:
 *    قبل تشغيل هذا الـ migration، حدّثي Notification.php Model
 *    (موجود في الـ zip) — أو بعد تشغيله مباشرة.
 *    أي تأخير = errors في الجرس.
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('notifications', 'app_notifications');
    }

    public function down(): void
    {
        Schema::rename('app_notifications', 'notifications');
    }
};
