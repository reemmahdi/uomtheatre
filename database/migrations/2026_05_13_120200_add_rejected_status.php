<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════════
 * Add 'rejected' status — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════════════
 *
 * 🎯 الهدف:
 *   إضافة حالة 'rejected' لجدول statuses لتمييز الفعاليات المرفوضة
 *   من مكتب الرئاسة (مختلفة عن 'cancelled' اللي تعني ألغاها المنشئ).
 *
 * 📊 الحالات بعد التحديث (8 حالات):
 *
 *   ┌─────────────┬─────────────────────────────────────────────┐
 *   │ الحالة       │ المعنى                                       │
 *   ├─────────────┼─────────────────────────────────────────────┤
 *   │ draft       │ مسودة (لسه ما اتسلّمت)                       │
 *   │ added       │ مرسلة لمكتب الرئاسة، تنتظر القرار              │
 *   │ rejected    │ ✨ جديد - رفضها مكتب الرئاسة                  │
 *   │ active      │ وافقت الرئاسة، تنتظر زر "نشر" من مدير الإعلام  │
 *   │ published   │ منشورة للجمهور 🎉                              │
 *   │ closed      │ الحجز مغلق (بعد البدء)                        │
 *   │ cancelled   │ ألغاها المنشئ                                 │
 *   │ end         │ انتهت الفعالية                                │
 *   └─────────────┴─────────────────────────────────────────────┘
 *
 * 💡 idempotent: استخدام updateOrInsert (تشغيل مرتين آمن)
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ════════════════════════════════════════════════════════════
        // ✨ PostgreSQL: مزامنة الـ sequence مع MAX(id) قبل أي INSERT
        // ════════════════════════════════════════════════════════════
        // المشكلة: الـ seeder يدخل records بـ IDs محددة (1, 2, 3...)
        //         لكن الـ sequence counter لسه عند 1. عندما updateOrInsert
        //         يحاول INSERT جديد، يستخدم الـ sequence → id=2 → فشل.
        //
        // الحل: setval(sequence, MAX(id)) قبل INSERT
        //
        // ملاحظة: في MySQL ما يحدث هذا (auto_increment يتحدّث تلقائياً)
        // ════════════════════════════════════════════════════════════
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('statuses', 'id'),
                    COALESCE((SELECT MAX(id) FROM statuses), 1),
                    true
                )
            ");
        }

        DB::table('statuses')->updateOrInsert(
            ['name' => 'rejected'],
            [
                'display_name' => 'مرفوضة',
                'description'  => 'رفضها مكتب الرئاسة - يمكن لمدير الإعلام تعديلها وإعادة الإرسال',
                'updated_at'   => $now,
                'created_at'   => $now,
            ]
        );
    }

    public function down(): void
    {
        // تحقّق إذا فيه فعاليات بحالة rejected قبل الحذف
        $statusId = DB::table('statuses')->where('name', 'rejected')->value('id');

        if (!$statusId) {
            return;
        }

        $eventsCount = DB::table('events')->where('status_id', $statusId)->count();

        if ($eventsCount > 0) {
            throw new \RuntimeException(
                "لا يمكن حذف حالة 'rejected' - يوجد {$eventsCount} فعالية تستخدمها"
            );
        }

        DB::table('statuses')->where('name', 'rejected')->delete();
    }
};
