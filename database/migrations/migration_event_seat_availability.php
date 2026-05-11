<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 * Migration للمرحلة 2.أ — UOMTheatre
 * ════════════════════════════════════════════════════════════════
 *
 * ينشئ جدول event_seat_availability لتحديد:
 *   - أي مقاعد متاحة للحجز عبر تطبيق الجمهور لكل فعالية
 *   - أي مقاعد مستبعدة (تظهر محجوزة في تطبيق الجمهور)
 *
 * منطق العمل:
 *   - لكل فعالية، إذا كان المقعد ليس له سجل في هذا الجدول
 *     أو is_public_available = false → يظهر محجوزاً للجمهور
 *   - فقط المقاعد التي لها سجل بـ is_public_available = true
 *     يستطيع الجمهور حجزها
 *
 * ════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_seat_availability', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();  // ✨ للحماية ضد IDOR

            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade');

            // الحقل الأساسي: هل المقعد متاح للجمهور؟
            $table->boolean('is_public_available')->default(true);

            // ملاحظة اختيارية: سبب الاستبعاد (مثل: "محجوز لوفد VIP")
            $table->string('exclusion_reason', 100)->nullable();

            // مَن قام بالتحديد ومتى
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // كل مقعد له سجل واحد فقط لكل فعالية
            $table->unique(['event_id', 'seat_id'], 'unique_event_seat');

            // فهرس للبحث السريع عن المقاعد المتاحة لفعالية
            $table->index(['event_id', 'is_public_available'], 'idx_event_availability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_seat_availability');
    }
};
