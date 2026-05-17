<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Restructure event_approvals — UOMTheatre (PostgreSQL-safe)
 * ════════════════════════════════════════════════════════════════════
 *
 * 🎯 الهدف:
 *   تبسيط جدول event_approvals ليطابق التصميم الجديد:
 *   ✅ إضافة:    round_number, rejection_reason
 *   ❌ حذف:      user_id, role_id, note, approved_at, rejected_at
 *   🔒 إضافة:    UNIQUE(event_id, round_number)
 *
 * 🔧 إصلاح PostgreSQL:
 *   النسخة السابقة كانت تستخدم:
 *     • SHOW INDEX FROM ... ← MySQL only
 *     • DB::getDatabaseName() كـ schema ← غلط في PostgreSQL
 *
 *   هذي النسخة تستخدم native DDL:
 *     • DROP CONSTRAINT IF EXISTS  ← آمن في PostgreSQL
 *     • DROP COLUMN IF EXISTS      ← آمن في كلا
 *     • DROP INDEX IF EXISTS       ← آمن في PostgreSQL
 *
 * ════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ────────────────────────────────────────────────────────
        // 1️⃣ إضافة round_number (idempotent)
        // ────────────────────────────────────────────────────────
        if (!Schema::hasColumn('event_approvals', 'round_number')) {
            Schema::table('event_approvals', function (Blueprint $table) {
                $table->unsignedTinyInteger('round_number')
                    ->default(1)
                    ->after('event_id')
                    ->comment('رقم الدورة - يزيد عند كل إعادة إرسال');
            });
        }

        // ────────────────────────────────────────────────────────
        // 2️⃣ إضافة rejection_reason (idempotent)
        // ────────────────────────────────────────────────────────
        if (!Schema::hasColumn('event_approvals', 'rejection_reason')) {
            Schema::table('event_approvals', function (Blueprint $table) {
                $table->text('rejection_reason')
                    ->nullable()
                    ->after('status')
                    ->comment('سبب الرفض (اختياري)');
            });
        }

        // ────────────────────────────────────────────────────────
        // 3️⃣ ترحيل البيانات: note → rejection_reason
        // ────────────────────────────────────────────────────────
        if (Schema::hasColumn('event_approvals', 'note')) {
            DB::table('event_approvals')
                ->where('status', 'rejected')
                ->whereNotNull('note')
                ->whereNull('rejection_reason')
                ->update([
                    'rejection_reason' => DB::raw('note'),
                ]);
        }

        // ────────────────────────────────────────────────────────
        // 4️⃣ حذف سجلات theater_manager (مشاهد فقط الآن)
        // ────────────────────────────────────────────────────────
        if (Schema::hasColumn('event_approvals', 'role_id')) {
            $theaterRoleId = DB::table('roles')
                ->where('name', 'theater_manager')
                ->value('id');

            if ($theaterRoleId) {
                DB::table('event_approvals')
                    ->where('role_id', $theaterRoleId)
                    ->delete();
            }
        }

        // ────────────────────────────────────────────────────────
        // 5️⃣ حذف سجلات pending
        // ────────────────────────────────────────────────────────
        DB::table('event_approvals')
            ->where('status', 'pending')
            ->delete();

        // ────────────────────────────────────────────────────────
        // 6️⃣ حذف الأعمدة القديمة (PostgreSQL-safe)
        // ────────────────────────────────────────────────────────

        // user_id: حذف FK + index + column
        $this->dropForeignKeyIfExists('event_approvals', 'event_approvals_user_id_foreign');
        $this->dropIndexIfExists('event_approvals', 'event_approvals_user_id_index');
        $this->dropColumnIfExists('event_approvals', 'user_id');

        // role_id: حذف FK + index + column
        $this->dropForeignKeyIfExists('event_approvals', 'event_approvals_role_id_foreign');
        $this->dropIndexIfExists('event_approvals', 'event_approvals_role_id_index');
        // كان فيه custom index قديم
        $this->dropIndexIfExists('event_approvals', 'event_approvals_role_id_status_index');
        $this->dropColumnIfExists('event_approvals', 'role_id');

        // أعمدة بدون FK
        $this->dropColumnIfExists('event_approvals', 'note');
        $this->dropColumnIfExists('event_approvals', 'approved_at');
        $this->dropColumnIfExists('event_approvals', 'rejected_at');

        // index قديم محتمل
        $this->dropIndexIfExists('event_approvals', 'unique_event_role_approval');

        // ────────────────────────────────────────────────────────
        // 7️⃣ إضافة UNIQUE constraint (idempotent)
        // ────────────────────────────────────────────────────────
        if (!$this->indexExists('event_approvals', 'event_approvals_event_round_unique')) {
            Schema::table('event_approvals', function (Blueprint $table) {
                $table->unique(
                    ['event_id', 'round_number'],
                    'event_approvals_event_round_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // 1) حذف UNIQUE
        $this->dropIndexIfExists('event_approvals', 'event_approvals_event_round_unique');

        // 2) إعادة الأعمدة القديمة
        Schema::table('event_approvals', function (Blueprint $table) {
            if (!Schema::hasColumn('event_approvals', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('event_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('event_approvals', 'role_id')) {
                $table->foreignId('role_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('roles')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('event_approvals', 'note')) {
                $table->text('note')->nullable()->after('status');
            }

            if (!Schema::hasColumn('event_approvals', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }

            if (!Schema::hasColumn('event_approvals', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
        });

        // 3) حذف الأعمدة الجديدة
        $this->dropColumnIfExists('event_approvals', 'round_number');
        $this->dropColumnIfExists('event_approvals', 'rejection_reason');
    }

    // ════════════════════════════════════════════════════════════════
    // ✨ Helpers آمنة لـ MySQL و PostgreSQL
    // ════════════════════════════════════════════════════════════════

    /**
     * حذف FK constraint لو موجود (PostgreSQL & MySQL)
     */
    protected function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                // PostgreSQL: native DDL
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
            } elseif ($driver === 'mysql') {
                // MySQL: نفحص أولاً
                if ($this->mysqlForeignKeyExists($table, $constraintName)) {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
                }
            }
        } catch (\Throwable $e) {
            // تجاهل بصمت لو FK غير موجود (يجي من بعض إصدارات MySQL)
        }
    }

    /**
     * حذف index لو موجود (PostgreSQL & MySQL)
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                // PostgreSQL: DROP INDEX IF EXISTS
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            } elseif ($driver === 'mysql') {
                if ($this->mysqlIndexExists($table, $indexName)) {
                    DB::statement("DROP INDEX `{$indexName}` ON `{$table}`");
                }
            }
        } catch (\Throwable $e) {
            // تجاهل
        }
    }

    /**
     * حذف عمود لو موجود (PostgreSQL & MySQL)
     */
    protected function dropColumnIfExists(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                // PostgreSQL: DROP COLUMN IF EXISTS مع CASCADE لإزالة الـ FK تلقائياً
                DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS {$column} CASCADE");
            } elseif ($driver === 'mysql') {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->dropColumn($column);
                });
            }
        } catch (\Throwable $e) {
            // log فقط، لا نوقف العملية
        }
    }

    /**
     * فحص وجود index (MySQL & PostgreSQL)
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $result = DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return !empty($result);
        }

        if ($driver === 'mysql') {
            return $this->mysqlIndexExists($table, $indexName);
        }

        return false;
    }

    /**
     * فحص FK في MySQL فقط
     */
    protected function mysqlForeignKeyExists(string $table, string $constraintName): bool
    {
        try {
            $database = DB::getDatabaseName();
            $result = DB::select(
                "SELECT constraint_name
                 FROM information_schema.table_constraints
                 WHERE table_schema = ?
                   AND table_name = ?
                   AND constraint_name = ?
                   AND constraint_type = 'FOREIGN KEY'",
                [$database, $table, $constraintName]
            );
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * فحص index في MySQL فقط
     */
    protected function mysqlIndexExists(string $table, string $indexName): bool
    {
        try {
            $result = DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
