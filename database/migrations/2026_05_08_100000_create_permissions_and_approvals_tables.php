<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        try {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `event_approvals` DROP FOREIGN KEY `event_approvals_role_id_foreign`");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE event_approvals DROP CONSTRAINT IF EXISTS event_approvals_role_id_foreign");
            }
        } catch (\Throwable $e) {
        }

        $indexes = [
            'event_approvals_role_id_index',
            'event_approvals_role_id_status_index',
        ];

        foreach ($indexes as $indexName) {
            try {
                if ($driver === 'mysql') {
                    DB::statement("DROP INDEX `{$indexName}` ON `event_approvals`");
                } elseif ($driver === 'pgsql') {
                    DB::statement("DROP INDEX IF EXISTS {$indexName}");
                }
            } catch (\Throwable $e) {
            }
        }

        Schema::table('event_approvals', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_approvals', 'role_id')) {
            return;
        }

        Schema::table('event_approvals', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('event_id')
                ->constrained('roles')
                ->nullOnDelete();
        });
    }
};
