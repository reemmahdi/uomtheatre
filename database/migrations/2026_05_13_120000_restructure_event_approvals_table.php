<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════════
 * Restructure event_approvals — UOMTheatre (مُصحَّحة - idempotent)
 * ════════════════════════════════════════════════════════════════════
 *
 * 🎯 الهدف:
 *   تبسيط جدول event_approvals ليطابق التصميم الجديد:
 *
 *   ✅ إضافة:    round_number, rejection_reason
 *   ❌ حذف:      user_id, role_id, note, approved_at, rejected_at
 *   🔒 إضافة:    UNIQUE(event_id, round_number)
 *
 * 🔧 إصلاح من النسخة السابقة:
 *   - حذف الـ index قبل العمود (السابقة كانت تحذف FK بس وتنسى الـ index)
 *   - كل عملية idempotent (آمن للتشغيل مرات متعددة)
 *
 * 💡 ملاحظة: event_logs يحفظ السجل التاريخي الكامل
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
        //    ملاحظة: لو role_id محذوف بالفعل من جدولة سابقة، هذا يتخطّى
        // ────────────────────────────────────────────────────────
        if (Schema::hasColumn('event_approvals', 'role_id')) {
            $theaterRoleId = DB::table('roles')
                ->where('name', 'theater_manager')
                ->value('id');

            if ($theaterRoleId) {
                $deletedTheater = DB::table('event_approvals')
                    ->where('role_id', $theaterRoleId)
                    ->delete();

                if ($deletedTheater > 0) {
                    echo "  🗑️  حُذفت {$deletedTheater} سجل موافقة لمدير المسرح\n";
                }
            }
        }

        // ────────────────────────────────────────────────────────
        // 5️⃣ حذف سجلات pending (التصميم الجديد ما ينشئ سجل قبل القرار)
        // ────────────────────────────────────────────────────────
        $deletedPending = DB::table('event_approvals')
            ->where('status', 'pending')
            ->delete();

        if ($deletedPending > 0) {
            echo "  🗑️  حُذفت {$deletedPending} سجل pending\n";
        }

        // ────────────────────────────────────────────────────────
        // 6️⃣ حذف الأعمدة القديمة بترتيب صحيح
        //    user_id و role_id يحتاجون: حذف FK → حذف index → حذف column
        // ────────────────────────────────────────────────────────
        $this->safeDropForeignColumn('event_approvals', 'user_id');
        $this->safeDropForeignColumn('event_approvals', 'role_id');

        // الأعمدة بدون FK
        Schema::table('event_approvals', function (Blueprint $table) {
            $simpleColumns = ['note', 'approved_at', 'rejected_at'];
            $existingColumns = array_values(array_filter(
                $simpleColumns,
                fn($col) => Schema::hasColumn('event_approvals', $col)
            ));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });

        // ────────────────────────────────────────────────────────
        // 7️⃣ إضافة UNIQUE constraint (idempotent)
        // ────────────────────────────────────────────────────────
        if (!$this->hasIndex('event_approvals', 'event_approvals_event_round_unique')) {
            Schema::table('event_approvals', function (Blueprint $table) {
                $table->unique(
                    ['event_id', 'round_number'],
                    'event_approvals_event_round_unique'
                );
            });
        }

        echo "  ✅ تم تطبيق التصميم الجديد لـ event_approvals\n";
    }

    public function down(): void
    {
        // 1) حذف UNIQUE constraint
        if ($this->hasIndex('event_approvals', 'event_approvals_event_round_unique')) {
            Schema::table('event_approvals', function (Blueprint $table) {
                $table->dropUnique('event_approvals_event_round_unique');
            });
        }

        // 2) إعادة الأعمدة القديمة (بدون البيانات - rollback غير كامل)
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
        Schema::table('event_approvals', function (Blueprint $table) {
            $newColumns = ['round_number', 'rejection_reason'];
            $existingNewColumns = array_values(array_filter(
                $newColumns,
                fn($col) => Schema::hasColumn('event_approvals', $col)
            ));

            if (!empty($existingNewColumns)) {
                $table->dropColumn($existingNewColumns);
            }
        });
    }

    /**
     * حذف عمود FK بأمان: FK → index → column
     * يتعامل مع كل الحالات الممكنة من المحاولات السابقة
     */
    protected function safeDropForeignColumn(string $tableName, string $columnName): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            return; // العمود محذوف بالفعل
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $columnName) {
            // 1) حذف FK constraint (لو موجود)
            $fkName = "{$tableName}_{$columnName}_foreign";
            if ($this->hasForeignKey($tableName, $fkName)) {
                $table->dropForeign($fkName);
            }

            // 2) حذف الـ index (مهم! الـ FK يخلّي index ورائه)
            $indexNames = [
                "{$tableName}_{$columnName}_index",
                $columnName,
            ];

            foreach ($indexNames as $indexName) {
                if ($this->hasIndex($tableName, $indexName)) {
                    $table->dropIndex($indexName);
                    break; // index واحد فقط عادة
                }
            }

            // 3) حذف العمود
            $table->dropColumn($columnName);
        });
    }

    /**
     * فحص وجود FK constraint بطريقة آمنة عبر MySQL وPostgreSQL
     */
    protected function hasForeignKey(string $tableName, string $constraintName): bool
    {
        $database = DB::connection()->getDatabaseName();

        return !empty(DB::select("
            SELECT constraint_name
            FROM information_schema.table_constraints
            WHERE table_schema = ?
              AND table_name = ?
              AND constraint_name = ?
              AND constraint_type = 'FOREIGN KEY'
        ", [$database, $tableName, $constraintName]));
    }

    /**
     * فحص وجود index بطريقة آمنة
     */
    protected function hasIndex(string $tableName, string $indexName): bool
    {
        try {
            return !empty(DB::select(
                "SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?",
                [$indexName]
            ));
        } catch (\Exception $e) {
            return false;
        }
    }
};
