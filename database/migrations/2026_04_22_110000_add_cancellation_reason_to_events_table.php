<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة عمود سبب الإلغاء لجدول الفعاليات
     *
     * الحقل:
     * - cancellation_reason: سبب إلغاء الفعالية (اختياري - nullable)
     * - cancelled_at: تاريخ الإلغاء (تلقائي)
     *
     * استخدام:
     * عند إلغاء فعالية، يُحفظ السبب هنا ويظهر في:
     * - جدول الفعاليات
     * - نافذة تفاصيل الفعالية
     * - إشعارات الإلغاء (لاحقاً)
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('closed_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancelled_at']);
        });
    }
};
