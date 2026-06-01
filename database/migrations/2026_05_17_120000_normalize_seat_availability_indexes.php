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

        if ($this->indexExists('event_seat_availability', 'unique_event_seat')) {
            Schema::table('event_seat_availability', function (Blueprint $table) {
                $table->dropUnique('unique_event_seat');
            });

            if (!$this->indexExists('event_seat_availability', 'unique_event_seat_availability')) {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->unique(['event_id', 'seat_id'], 'unique_event_seat_availability');
                });
            }
        }

    }

    public function down(): void
    {
        if (!Schema::hasTable('event_seat_availability')) {
            return;
        }

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
