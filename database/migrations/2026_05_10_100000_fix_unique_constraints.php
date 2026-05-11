<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 * إصلاح unique constraints
 * ════════════════════════════════════════════════════════════════
 *
 * المشكلة:
 *   - في PostgreSQL: لا يمكن وجود index بنفس الاسم في جدولين مختلفين
 *   - في MySQL: يعمل لكن مشكلة عند Migration للـ Cloud
 *
 * الحل:
 *   - حذف الـ unique constraint القديم
 *   - إضافته بأسماء فريدة لكل جدول
 *
 * ✨ نسخة آمنة (idempotent):
 *   - تفحص وجود كل index قبل الحذف
 *   - تفحص وجود الـ index الجديد قبل الإضافة
 *   - تعمل مع MySQL و PostgreSQL
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

        // sqlite أو غيره - نعتبر موجود (سيُلتقط في catch)
        return true;
    }

    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. event_seat_availability table
        // ─────────────────────────────────────────────

        // ✨ حذف القديم (لو موجود بأي اسم محتمل)
        $oldNamesAvailability = [
            'unique_event_seat',
            'event_seat_availability_event_id_seat_id_unique',
        ];

        foreach ($oldNamesAvailability as $name) {
            if ($this->indexExists('event_seat_availability', $name)) {
                Schema::table('event_seat_availability', function (Blueprint $table) use ($name) {
                    $table->dropUnique($name);
                });
            }
        }

        // ✨ إضافة الـ unique الجديد (لو ما موجود)
        if (!$this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->unique(['event_id', 'seat_id'], 'unique_event_seat_availability');
            });
        }

        // ─────────────────────────────────────────────
        // 2. reservations table
        // ─────────────────────────────────────────────
        // ملاحظة: نحذف الـ UNIQUE من reservations نهائياً
        // لأنه يمنع re-booking بعد cancellation
        // ─────────────────────────────────────────────

        $oldNamesReservations = [
            'unique_event_seat',
            'reservations_event_id_seat_id_unique',
        ];

        foreach ($oldNamesReservations as $name) {
            if ($this->indexExists('reservations', $name)) {
                Schema::table('reservations', function (Blueprint $table) use ($name) {
                    $table->dropUnique($name);
                });
            }
        }
    }

    public function down(): void
    {
        // إعادة الـ index في event_seat_availability
        if ($this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->dropUnique('unique_event_seat_availability');
            });
        }

        if (!$this->indexExists('event_seat_availability', 'unique_event_seat')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->unique(['event_id', 'seat_id'], 'unique_event_seat');
            });
        }

        // لا نُعيد UNIQUE على reservations (تم حذفه عمداً)
    }
};
