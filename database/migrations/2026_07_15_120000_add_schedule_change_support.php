<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ميزة تأجيل الفعالية مع مهلة تأكيد 24 ساعة:
 * - جدول سجل تغييرات المواعيد (المتطلب 8: التوثيق)
 * - أعمدة التأكيد على الحجوزات (المتطلبات 3-7)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_schedule_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->dateTime('old_start_datetime');
            $table->dateTime('old_end_datetime');
            $table->dateTime('new_start_datetime');
            $table->dateTime('new_end_datetime');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            // أي تغيير موعد ينتظر تأكيد صاحب الحجز؟
            $table->foreignId('schedule_change_id')
                ->nullable()
                ->after('qr_code')
                ->constrained('event_schedule_changes')
                ->nullOnDelete();
            // آخر مهلة للتأكيد (24 ساعة أو بداية الفعالية أيهما أقرب)
            $table->timestamp('confirm_until')->nullable()->after('schedule_change_id');
            // متى أكد صاحب الحجز الموعد الجديد (null = لم يؤكد بعد)
            $table->timestamp('change_confirmed_at')->nullable()->after('confirm_until');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_change_id');
            $table->dropColumn(['confirm_until', 'change_confirmed_at']);
        });
        Schema::dropIfExists('event_schedule_changes');
    }
};
