<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة دعم إيقاف/استئناف الحجز للفعاليات
     *
     * is_booking_paused: هل الحجوزات الجديدة موقوفة مؤقتاً؟
     * paused_at: متى تم الإيقاف (للسجلات والعرض)
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_booking_paused')
                ->default(false)
                ->after('cancelled_at')
                ->comment('هل الحجوزات الجديدة موقوفة مؤقتاً');

            $table->timestamp('paused_at')
                ->nullable()
                ->after('is_booking_paused')
                ->comment('وقت إيقاف الحجوزات');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_booking_paused', 'paused_at']);
        });
    }
};
