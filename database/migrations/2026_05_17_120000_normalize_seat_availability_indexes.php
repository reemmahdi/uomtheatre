<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 * Normalize Seat Availability Index Names
 * ════════════════════════════════════════════════════════════════
 *
 * المشكلة:
 *   في PostgreSQL (Laravel Cloud)، الـ migrations تشتغل بترتيب أبجدي:
 *     1. 2026_05_10_100000_fix_unique_constraints  ← يُنفَّذ أولاً ويتجاوز
 *     2. migration_event_seat_availability         ← ينشئ الجدول بـ index قديم
 *
 *   نتيجة: الـ DB ينتهي بـ index اسمه 'unique_event_seat'
 *           بدل الاسم الموحَّد 'unique_event_seat_availability'.
 *
 * الحل:
 *   هذا الـ migration يصلح الاسم بعد إنشاء الجدول.
 *
 * Idempotent:
 *   - محلياً (MySQL): الـ index الصحيح موجود → ما يفعل شي
 *   - PostgreSQL: يجد الـ index القديم → يبدّله
 *
 * ════════════════════════════════════════════════════════════════
 */
return new class extends Migration {

    /**
     * فحص وجود index في جدول معيّن
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $result = DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return !empty($result);
        }

        if ($driver === 'pgsql') {
            $result = DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return !empty($result);
        }

        return true;
    }

    public function up(): void
    {
        // تجاوز لو الجدول غير موجود (سلامة إضافية)
        if (!Schema::hasTable('event_seat_availability')) {
            return;
        }

        // ✨ لو الـ index القديم موجود → احذفه وأضف الجديد
        if ($this->indexExists('event_seat_availability', 'unique_event_seat')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->dropUnique('unique_event_seat');
            });

            // أضف الـ index الجديد لو ما موجود
            if (!$this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->unique(['event_id', 'seat_id'], 'unique_event_seat_availability');
                });
            }
        }

        // محلياً (MySQL): الـ index الصحيح موجود بالفعل → ما يفعل شي ✓
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_seat_availability')) {
            return;
        }

        // ارجاع الاسم القديم
        if ($this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->dropUnique('unique_event_seat_availability');
            });

            if (!$this->indexExists('event_seat_availability', 'unique_event_seat')) {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->unique(['event_id', 'seat_id'], 'unique_event_seat');
                });
            }
        }
    }
};
