<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Add Performance Indexes — UOMTheatre (إصلاح Claude)
 * ════════════════════════════════════════════════════════════════════
 *
 * إضافة indexes مفقودة لتحسين أداء الاستعلامات الشائعة:
 *
 *   📊 events:
 *      - status_id  → "كل الفعاليات published" / "بانتظار الموافقة"
 *      - start_datetime → "الفعاليات القادمة" / "الجارية حالياً"
 *
 *   📊 reservations:
 *      - event_id  → "مقاعد فعالية" (الأهم!)
 *      - (user_id, status) → "حجوزاتي النشطة" في تطبيق الجمهور
 *
 *   📊 event_logs:
 *      - event_id  → "timeline فعالية" (المرحلة 3.ب)
 *
 *   📊 app_notifications:
 *      - (user_id, is_read) → الجرس - عدد الإشعارات غير المقروءة
 *
 * ⚠️ هذا الـ migration يأتي بعد rename لـ app_notifications
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // events
        // ═══════════════════════════════════════════════════════
        Schema::table('events', function (Blueprint $table) {
            $table->index('status_id', 'idx_events_status');
            $table->index('start_datetime', 'idx_events_start');
        });

        // ═══════════════════════════════════════════════════════
        // reservations
        // ═══════════════════════════════════════════════════════
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('event_id', 'idx_reservations_event');
            $table->index(['user_id', 'status'], 'idx_reservations_user_status');
        });

        // ═══════════════════════════════════════════════════════
        // event_logs
        // ═══════════════════════════════════════════════════════
        Schema::table('event_logs', function (Blueprint $table) {
            $table->index('event_id', 'idx_event_logs_event');
        });

        // ═══════════════════════════════════════════════════════
        // app_notifications (يجب أن يكون الجدول مُعاد تسميته أولاً)
        // ═══════════════════════════════════════════════════════
        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->index(['user_id', 'is_read'], 'idx_notifications_user_unread');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status');
            $table->dropIndex('idx_events_start');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_event');
            $table->dropIndex('idx_reservations_user_status');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex('idx_event_logs_event');
        });

        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_user_unread');
            });
        }
    }
};
