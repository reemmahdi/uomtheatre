<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Add published_by to events — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════════════
 *
 * 🎯 الهدف:
 *   إضافة عمود published_by لتتبّع مين نشر الفعالية فعلاً.
 *
 *   التصميم الجديد يفصل بين:
 *     - created_by    → مين أنشأ الفعالية (مدير الإعلام)
 *     - published_by  → مين ضغط "نشر" بعد الموافقة
 *
 *   في 99% من الحالات هم نفس الشخص، لكن:
 *     - السوبر أدمن يگدر ينشر بدل المنشئ (لو غاب مثلاً)
 *     - مهم للسجل والمحاسبة
 *
 * 📊 التغييرات:
 *
 *   ✅ إضافة:    published_by   ← FK → users (nullable)
 *
 * 💡 backfill:
 *   للفعاليات المنشورة سابقاً (published_at IS NOT NULL):
 *   نضع published_by = created_by (افتراض معقول لأنه التصميم القديم
 *   كان النشر تلقائي بمجرد اكتمال الموافقات).
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ────────────────────────────────────────────────────────
        // 1️⃣ إضافة العمود (nullable عشان السجلات القديمة)
        // ────────────────────────────────────────────────────────
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('published_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('المستخدم الذي ضغط زر النشر');
        });

        // ────────────────────────────────────────────────────────
        // 2️⃣ Backfill: للفعاليات المنشورة سابقاً، استخدم created_by
        // ────────────────────────────────────────────────────────
        $backfilled = DB::table('events')
            ->whereNotNull('published_at')
            ->whereNull('published_by')
            ->update([
                'published_by' => DB::raw('created_by'),
            ]);

        if ($backfilled > 0) {
            echo "  ✅ تم backfill {$backfilled} فعالية منشورة سابقاً\n";
        }

        echo "  ✅ تم إضافة عمود published_by لجدول events\n";
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn('published_by');
        });
    }
};
