<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        $events = DB::table('events')->whereNull('uuid')->get();

        foreach ($events as $event) {
            DB::table('events')
                ->where('id', $event->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid', 'events_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
