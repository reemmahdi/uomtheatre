<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_approvals', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('event_id')->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
