<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('statuses', 'id'),
                    COALESCE((SELECT MAX(id) FROM statuses), 1),
                    true
                )
            ");
        }

        DB::table('statuses')->updateOrInsert(
            ['name' => 'rejected'],
            [
                'display_name' => 'مرفوضة',
                'description'  => 'رفضها مكتب الرئاسة - يمكن لمدير الإعلام تعديلها وإعادة الإرسال',
                'updated_at'   => $now,
                'created_at'   => $now,
            ]
        );
    }

    public function down(): void
    {
        $statusId = DB::table('statuses')->where('name', 'rejected')->value('id');

        if (!$statusId) {
            return;
        }

        $eventsCount = DB::table('events')->where('status_id', $statusId)->count();

        if ($eventsCount > 0) {
            throw new \RuntimeException(
                "لا يمكن حذف حالة 'rejected' - يوجد {$eventsCount} فعالية تستخدمها"
            );
        }

        DB::table('statuses')->where('name', 'rejected')->delete();
    }
};
