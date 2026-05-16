<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════
 * Status Model — UOMTheatre
 * ════════════════════════════════════════════════════════════
 *
 * يمثّل حالات الفعالية في النظام.
 *
 * 🎯 التغيير: إضافة REJECTED constant
 *
 * 📊 الحالات (8 حالات):
 *
 *   draft        مسودة (لسه ما اتسلّمت)
 *   added        مرسلة لمكتب الرئاسة، تنتظر القرار
 *   under_review (احتياطي - غير مستخدمة فعلياً)
 *   rejected     ✨ جديد - رفضها مكتب الرئاسة
 *   active       وافقت الرئاسة، تنتظر زر "نشر" من مدير الإعلام
 *   published    منشورة للجمهور 🎉
 *   closed       الحجز مغلق (بعد البدء)
 *   cancelled    ألغاها المنشئ
 *   end          انتهت الفعالية
 *
 * 🔄 سير الحياة (Lifecycle):
 *
 *   draft → added → rejected → (تعديل) → added → ... (دورة)
 *                ↓
 *                active → published → closed → end
 *                                  ↓
 *                                  cancelled
 *
 * ════════════════════════════════════════════════════════════
 */
class Status extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    const DRAFT        = 'draft';
    const ADDED        = 'added';
    const UNDER_REVIEW = 'under_review';
    const REJECTED     = 'rejected';  // ✨ جديد
    const ACTIVE       = 'active';
    const PUBLISHED    = 'published';
    const CLOSED       = 'closed';
    const CANCELLED    = 'cancelled';
    const END          = 'end';

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
