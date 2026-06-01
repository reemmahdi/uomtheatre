<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    private const MIN_LENGTH = 12;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('كلمة المرور يجب أن تكون نصاً');
            return;
        }

        $errors = [];

        if (strlen($value) < self::MIN_LENGTH) {
            $errors[] = self::MIN_LENGTH . ' رمز على الأقل';
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'حرف كبير واحد على الأقل';
        }

        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'حرف صغير واحد على الأقل';
        }

        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'رقم واحد على الأقل';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            $errors[] = 'رمز خاص واحد على الأقل (مثل @ # $ %)';
        }

        if (!empty($errors)) {
            $fail('كلمة المرور يجب أن تحتوي على: ' . implode('، ', $errors));
        }
    }
}
