<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 * Migration للمرحلة 2.أ — UOMTheatre (PostgreSQL-safe)
 * ════════════════════════════════════════════════════════════════
 *
 * ينشئ جدول event_seat_availability لتحديد:
 *   - أي مقاعد متاحة للحجز عبر تطبيق الجمهور لكل فعالية
 *   - أي مقاعد مستبعدة (تظهر محجوزة في تطبيق الجمهور)
 *
 * 🔧 إصلاح PostgreSQL:
 *   - فحص وجود الجدول قبل create (idempotent)
 *   - فحص وجود الـ indexes قبل الإضافة
 *   - في PostgreSQL أسماء الـ indexes فريدة على مستوى DB كامل
 *
 * ════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ✨ idempotent: إذا الجدول موجود بالفعل (محاولة سابقة فاشلة)
        if (Schema::hasTable('event_seat_availability')) {
            $this->ensureIndexes();
            return;
        }

        Schema::create('event_seat_availability', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade');

            $table->boolean('is_public_available')->default(true);

            $table->string('exclusion_reason', 100)->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // ✨ إضافة الـ indexes بعد إنشاء الجدول (idempotent)
        $this->ensureIndexes();
    }

    /**
     * إضافة الـ indexes بأمان (idempotent)
     */
    private function ensureIndexes(): void
    {
        // index 1: unique على (event_id, seat_id)
        if (!$this->indexExists('event_seat_availability', 'unique_event_seat')) {
            try {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->unique(['event_id', 'seat_id'], 'unique_event_seat');
                });
            } catch (\Throwable $e) {
                // تجاهل
            }
        }

        // index 2: للبحث السريع
        if (!$this->indexExists('event_seat_availability', 'idx_event_availability')) {
            try {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->index(['event_id', 'is_public_available'], 'idx_event_availability');
                });
            } catch (\Throwable $e) {
                // تجاهل
            }
        }
    }

    /**
     * فحص وجود index (MySQL & PostgreSQL)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                $result = DB::select(
                    "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                    [$table, $indexName]
                );
                return !empty($result);
            }

            if ($driver === 'mysql') {
                $result = DB::select(
                    "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                    [$indexName]
                );
                return !empty($result);
            }
        } catch (\Throwable $e) {
            // تجاهل
        }

        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('event_seat_availability');
    }
};
