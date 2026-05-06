<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * ============================================================
 * HasUuid Trait
 * ============================================================
 * توليد UUID تلقائياً عند إنشاء سجل جديد
 * + استعمال UUID للـ Route Model Binding (بدل ID رقمي)
 *
 * الاستخدام في Model:
 *   use App\Models\Traits\HasUuid;
 *
 *   class Event extends Model {
 *       use HasUuid;
 *       // ...
 *   }
 *
 * النتيجة:
 *   - عند إنشاء فعالية جديدة، يتولّد UUID تلقائياً
 *   - الروابط تستعمل UUID بدل ID رقمي
 *   - ID الرقمي يبقى موجود داخلياً للأداء
 * ============================================================
 */
trait HasUuid
{
    /**
     * عند تسجيل Model، نضيف listener لتوليد UUID
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * تحديد العمود المستخدم في Route Model Binding
     * بدل id الرقمي، نستعمل uuid
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
