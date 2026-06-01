<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        $this->ensureIndexes();
    }

    private function ensureIndexes(): void
    {
        if (!$this->indexExists('event_seat_availability', 'unique_event_seat')) {
            try {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->unique(['event_id', 'seat_id'], 'unique_event_seat');
                });
            } catch (\Throwable $e) {
            }
        }

        if (!$this->indexExists('event_seat_availability', 'idx_event_availability')) {
            try {
                Schema::table('event_seat_availability', function (Blueprint $table) {
                    $table->index(['event_id', 'is_public_available'], 'idx_event_availability');
                });
            } catch (\Throwable $e) {
            }
        }
    }

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
        }

        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('event_seat_availability');
    }
};
