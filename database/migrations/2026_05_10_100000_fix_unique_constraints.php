<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
        if (!Schema::hasTable('event_seat_availability')) {
            return;
        }

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

        if (!$this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->unique(['event_id', 'seat_id'], 'unique_event_seat_availability');
            });
        }

        if (!Schema::hasTable('reservations')) {
            return;
        }

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

    }
};
