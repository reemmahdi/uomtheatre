<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ============================================================
 * Add UUID column to events table
 * ============================================================
 * هذا الـ migration:
 * 1. يضيف عمود uuid (نوع uuid native لـ PostgreSQL، string لـ MySQL)
 * 2. يولّد UUID فريد لكل سجل موجود
 * 3. يجعل العمود NOT NULL + UNIQUE + INDEX (للأداء)
 *
 * الفائدة: حماية ضد IDOR (Insecure Direct Object Reference)
 * URLs قبل: /dashboard/events/12/vip-booking
 * URLs بعد: /dashboard/events/9f1d2c4a-3b5e-4f78-9c12-abcd1234ef56/vip-booking
 * ============================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // الخطوة 1: إضافة العمود (nullable مؤقتاً)
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // الخطوة 2: تعبئة UUID لكل السجلات الموجودة
        $events = DB::table('events')->whereNull('uuid')->get();

        foreach ($events as $event) {
            DB::table('events')
                ->where('id', $event->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // الخطوة 3: جعل العمود NOT NULL + UNIQUE + INDEX
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid', 'events_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
