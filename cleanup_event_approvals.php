<?php

/**
 * ════════════════════════════════════════════════════════════════
 * Cleanup Script — event_approvals
 * ════════════════════════════════════════════════════════════════
 *
 * طريقة التشغيل:
 *   php artisan tinker --execute="require 'cleanup_event_approvals.php';"
 *
 * أو:
 *   php artisan tinker
 *   > require 'cleanup_event_approvals.php';
 *
 * هذا السكريبت يحذف الأعمدة القديمة من event_approvals بطريقة آمنة:
 *   - يكتشف FKs ديناميكياً
 *   - يحذف كل indexes تستخدم العمود
 *   - يحذف العمود نفسه
 * ════════════════════════════════════════════════════════════════
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columnsToRemove = ['user_id', 'role_id', 'note', 'approved_at', 'rejected_at'];

echo "═══ بدء التنظيف ═══\n";

foreach ($columnsToRemove as $col) {
    if (!Schema::hasColumn('event_approvals', $col)) {
        echo "⏭️  {$col} محذوف بالفعل\n";
        continue;
    }

    echo "\n🔍 معالجة: {$col}\n";

    // 1. اكتشف وأحذف الـ FKs
    $fks = DB::select("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'event_approvals'
          AND TABLE_SCHEMA = DATABASE()
          AND COLUMN_NAME = ?
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ", [$col]);

    foreach ($fks as $fk) {
        try {
            DB::statement("ALTER TABLE event_approvals DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            echo "  🔓 FK: {$fk->CONSTRAINT_NAME}\n";
        } catch (\Exception $e) {
            echo "  ⚠️  FK {$fk->CONSTRAINT_NAME}: " . $e->getMessage() . "\n";
        }
    }

    // 2. اكتشف وأحذف الـ indexes
    $indexes = DB::select("
        SHOW INDEX FROM event_approvals
        WHERE Column_name = ? AND Key_name != 'PRIMARY'
    ", [$col]);

    $uniqueIndexNames = collect($indexes)->pluck('Key_name')->unique();

    foreach ($uniqueIndexNames as $idx) {
        try {
            DB::statement("ALTER TABLE event_approvals DROP INDEX `{$idx}`");
            echo "  📇 INDEX: {$idx}\n";
        } catch (\Exception $e) {
            echo "  ⚠️  Index {$idx}: " . $e->getMessage() . "\n";
        }
    }

    // 3. أحذف العمود
    try {
        DB::statement("ALTER TABLE event_approvals DROP COLUMN `{$col}`");
        echo "  ✅ DROPPED: {$col}\n";
    } catch (\Exception $e) {
        echo "  ❌ فشل حذف {$col}: " . $e->getMessage() . "\n";
    }
}

// إضافة UNIQUE constraint
echo "\n🔒 إضافة UNIQUE constraint\n";
$existing = DB::select("SHOW INDEX FROM event_approvals WHERE Key_name = 'event_approvals_event_round_unique'");

if (empty($existing)) {
    try {
        DB::statement("ALTER TABLE event_approvals ADD UNIQUE KEY event_approvals_event_round_unique (event_id, round_number)");
        echo "  ✅ UNIQUE(event_id, round_number) أُضيف\n";
    } catch (\Exception $e) {
        echo "  ⚠️  UNIQUE: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ⏭️  UNIQUE موجود بالفعل\n";
}

// عرض الأعمدة النهائية
echo "\n═══ النتيجة النهائية ═══\n";
$columns = Schema::getColumnListing('event_approvals');
echo "الأعمدة: " . implode(', ', $columns) . "\n";
echo "العدد: " . count($columns) . " عمود\n";

if (count($columns) === 8) {
    echo "\n🎉 ممتاز! الجدول نظيف وجاهز.\n";
} else {
    echo "\n⚠️  العدد المتوقع: 8. تحقّقي من النتيجة.\n";
}
