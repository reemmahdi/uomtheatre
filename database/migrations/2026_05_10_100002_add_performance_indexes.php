<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('status_id', 'idx_events_status');
            $table->index('start_datetime', 'idx_events_start');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('event_id', 'idx_reservations_event');
            $table->index(['user_id', 'status'], 'idx_reservations_user_status');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->index('event_id', 'idx_event_logs_event');
        });

        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->index(['user_id', 'is_read'], 'idx_notifications_user_unread');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status');
            $table->dropIndex('idx_events_start');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_event');
            $table->dropIndex('idx_reservations_user_status');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex('idx_event_logs_event');
        });

        if (Schema::hasTable('app_notifications')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_user_unread');
            });
        }
    }
};
