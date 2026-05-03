<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * قاعدة التحقق من قوة كلمة المرور
 *
 * الشروط:
 * - 12 رمز على الأقل
 * - حرف كبير واحد على الأقل (A-Z)
 * - حرف صغير واحد على الأقل (a-z)
 * - رقم واحد على الأقل (0-9)
 * - رمز خاص واحد على الأقل (@#$%^&* إلخ)
 *
 * هذه القاعدة تتوافق مع معايير:
 * - NIST SP 800-63B (معيار كلمات المرور الأمريكي)
 * - OWASP ASVS (معايير التطبيقات الآمنة)
 */
class StrongPassword implements ValidationRule
{
    /**
     * الحد الأدنى لطول كلمة المرور
     */
    private const MIN_LENGTH = 12;

    /**
     * تنفيذ قاعدة التحقق
     *
     * @param  string  $attribute  اسم الحقل
     * @param  mixed   $value      قيمة كلمة المرور المُدخلة
     * @param  \Closure  $fail     دالة لإرسال رسالة الخطأ
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // تأكد من أن القيمة نص
        if (!is_string($value)) {
            $fail('كلمة المرور يجب أن تكون نصاً');
            return;
        }

        $errors = [];

        // 1. التحقق من الطول
        if (strlen($value) < self::MIN_LENGTH) {
            $errors[] = self::MIN_LENGTH . ' رمز على الأقل';
        }

        // 2. التحقق من وجود حرف كبير
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'حرف كبير واحد على الأقل';
        }

        // 3. التحقق من وجود حرف صغير
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'حرف صغير واحد على الأقل';
        }

        // 4. التحقق من وجود رقم
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'رقم واحد على الأقل';
        }

        // 5. التحقق من وجود رمز خاص
        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            $errors[] = 'رمز خاص واحد على الأقل (مثل @ # $ %)';
        }

        // إذا وُجدت أخطاء، أرسلها في رسالة واحدة واضحة
        if (!empty($errors)) {
            $fail('كلمة المرور يجب أن تحتوي على: ' . implode('، ', $errors));
        }
    }
}
