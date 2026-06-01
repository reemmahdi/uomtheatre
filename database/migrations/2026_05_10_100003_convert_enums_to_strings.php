<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
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
