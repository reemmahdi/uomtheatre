<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('published_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('المستخدم الذي ضغط زر النشر');
        });

        $backfilled = DB::table('events')
            ->whereNotNull('published_at')
            ->whereNull('published_by')
            ->update([
                'published_by' => DB::raw('created_by'),
            ]);

        if ($backfilled > 0) {
            echo "  تم backfill {$backfilled} فعالية منشورة سابقاً\n";
        }

        echo "  تم إضافة عمود published_by لجدول events\n";
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn('published_by');
        });
    }
};
