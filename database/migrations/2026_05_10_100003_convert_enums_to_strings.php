<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Convert Enums to Strings — UOMTheatre (إصلاح Claude)
 * ════════════════════════════════════════════════════════════════════
 *
 * المشكلة:
 *   - enum في MySQL/PostgreSQL صعب التعديل
 *   - إضافة قيمة جديدة (مثل 'no_show', 'expired', 'refunded')
 *     تحتاج migration معقّد
 *   - PostgreSQL يخلق ENUM type منفصل صعب الإدارة
 *
 * ✅ الحل: تحويل enum إلى string(20) + validation في الكود
 *    البيانات نفسها تبقى كما هي (نفس النصوص)
 *
 * الأعمدة المُحوَّلة:
 *   - reservations.status: ['confirmed', 'cancelled', 'checked_in']
 *   - reservations.type:   ['regular', 'vip_guest']
 *   - event_approvals.status: ['pending', 'approved', 'rejected']
 *
 * 💡 لا تنسي إضافة validation في الـ Models أو Form Requests
 *    للحفاظ على نفس قيود enum.
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: يحتاج USING clause للتحويل من enum إلى varchar
            DB::statement(
                "ALTER TABLE reservations ALTER COLUMN status TYPE VARCHAR(20) USING status::text"
            );
            DB::statement(
                "ALTER TABLE reservations ALTER COLUMN status SET DEFAULT 'confirmed'"
            );

            DB::statement(
                "ALTER TABLE reservations ALTER COLUMN type TYPE VARCHAR(20) USING type::text"
            );
            DB::statement(
                "ALTER TABLE reservations ALTER COLUMN type SET DEFAULT 'regular'"
            );

            DB::statement(
                "ALTER TABLE event_approvals ALTER COLUMN status TYPE VARCHAR(20) USING status::text"
            );
            DB::statement(
                "ALTER TABLE event_approvals ALTER COLUMN status SET DEFAULT 'pending'"
            );
        } else {
            // MySQL/MariaDB: change() يعمل مباشرة في Laravel 11+
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('status', 20)->default('confirmed')->change();
                $table->string('type', 20)->default('regular')->change();
            });

            Schema::table('event_approvals', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: استعادة enum (معقّد - نتركه varchar إذا rollback)
            // ملاحظة: في PostgreSQL، استعادة الـ ENUM type تحتاج CREATE TYPE أولاً
            // لذا في الـ down نكتفي بالعودة إلى varchar مع check constraint
            DB::statement("
                ALTER TABLE reservations
                ADD CONSTRAINT reservations_status_check
                CHECK (status IN ('confirmed', 'cancelled', 'checked_in'))
            ");
            DB::statement("
                ALTER TABLE reservations
                ADD CONSTRAINT reservations_type_check
                CHECK (type IN ('regular', 'vip_guest'))
            ");
            DB::statement("
                ALTER TABLE event_approvals
                ADD CONSTRAINT event_approvals_status_check
                CHECK (status IN ('pending', 'approved', 'rejected'))
            ");
        } else {
            // MySQL: العودة إلى enum
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('status', ['confirmed', 'cancelled', 'checked_in'])
                    ->default('confirmed')->change();
                $table->enum('type', ['regular', 'vip_guest'])
                    ->default('regular')->change();
            });

            Schema::table('event_approvals', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('pending')->change();
            });
        }
    }
};
