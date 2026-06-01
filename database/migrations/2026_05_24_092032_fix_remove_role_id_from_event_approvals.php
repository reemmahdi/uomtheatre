<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('event_approvals', 'role_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE event_approvals ALTER COLUMN role_id DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE `event_approvals` MODIFY `role_id` BIGINT UNSIGNED NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('event_approvals', 'role_id')) {
            return;
        }

        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE event_approvals ALTER COLUMN role_id SET NOT NULL');
            } else {
                DB::statement('ALTER TABLE `event_approvals` MODIFY `role_id` BIGINT UNSIGNED NOT NULL');
            }
        } catch (\Throwable $e) {
        }
    }
};
