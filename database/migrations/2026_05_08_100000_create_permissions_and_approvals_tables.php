<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════
 * Migration للمرحلة 1.أ — UOMTheatre
 * ════════════════════════════════════════════════════════════
 *
 * ينشئ ثلاثة جداول جديدة:
 *
 * 1. permissions          : قائمة الصلاحيات في النظام
 * 2. role_permission      : ربط الأدوار بالصلاحيات (Pivot)
 * 3. event_approvals      : موافقات كل دور على كل فعالية (متوازي)
 *
 * ════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ════════════════════════════════════════════════════════
        // 1. جدول permissions — قائمة الصلاحيات
        // ════════════════════════════════════════════════════════
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();           // مثل: events.create
            $table->string('display_name');             // مثل: إنشاء فعالية
            $table->text('description')->nullable();    // وصف تفصيلي
            $table->string('group')->default('general'); // للتجميع في الشاشة
            $table->timestamps();
        });

        // ════════════════════════════════════════════════════════
        // 2. جدول role_permission — Pivot لربط الأدوار بالصلاحيات
        // ════════════════════════════════════════════════════════
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            // كل دور لا يمكن أن يحصل على نفس الصلاحية مرتين
            $table->unique(['role_id', 'permission_id']);
        });

        // ════════════════════════════════════════════════════════
        // 3. جدول event_approvals — موافقات الفعاليات
        //
        // كل فعالية تحتاج موافقتين (مدير المسرح + مكتب الرئيس)
        // كل موافقة تُسجَّل بشكل منفصل مع timestamp
        //
        // ✨ يستخدم UUID للحماية ضد IDOR (مثل جدول events)
        // ════════════════════════════════════════════════════════
        Schema::create('event_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();  // ✨ للحماية ضد IDOR

            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles');

            // حالة الموافقة: pending / approved / rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // ملاحظة اختيارية (سبب الرفض مثلاً)
            $table->text('note')->nullable();

            // timestamps للتتبع
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // كل دور لا يمكنه الموافقة على نفس الفعالية مرتين
            $table->unique(['event_id', 'role_id'], 'unique_event_role_approval');

            // فهرس للبحث السريع عن الفعاليات بانتظار موافقة دور معيّن
            $table->index(['role_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_approvals');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};
