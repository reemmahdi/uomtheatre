<?php

namespace App\Livewire\Auth;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * ════════════════════════════════════════════════════════════════
 * Login — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 إضافة rate limiting (حماية من brute force)
 *   🟡 nullsafe على role + استخدام Role::USER constant
 *   🟡 مسح password بعد كل محاولة
 *
 * ════════════════════════════════════════════════════════════════
 */
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public string $errorMessage = '';

    /**
     * ✨ مفتاح rate limiting (مرتبط بـ email + IP لتقليل false positives)
     */
    protected function throttleKey(): string
    {
        return Str::lower($this->email) . '|' . request()->ip();
    }

    public function login()
    {
        $this->errorMessage = '';

        // ✨ rate limiting: max 5 attempts per minute
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->errorMessage = "محاولات كثيرة. حاولي مرة أخرى بعد {$seconds} ثانية";
            $this->password = '';   // مسح للأمان
            return;
        }

        // التحقق من البيانات
        try {
            $this->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ], [
                'email.required'    => 'يرجى إدخال البريد الإلكتروني',
                'email.email'       => 'صيغة البريد الإلكتروني غير صحيحة',
                'password.required' => 'يرجى إدخال كلمة المرور',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            $this->errorMessage = $errors[0] ?? 'بيانات الدخول غير صحيحة';
            return;
        }

        // محاولة تسجيل الدخول
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $user = Auth::user();

            // التحقق من تفعيل الحساب
            if (!$user->is_active) {
                Auth::logout();
                $this->errorMessage = 'حسابك معطّل. يرجى التواصل مع الإدارة';
                $this->password = '';
                return;
            }

            // ✨ منع المستخدمين العاديين (nullsafe + constant)
            if ($user->role?->name === Role::USER) {
                Auth::logout();
                $this->errorMessage = 'لوحة التحكم مخصصة للموظفين. استخدمي التطبيق للحجز';
                $this->password = '';
                return;
            }

            // ✨ نجاح: مسح rate limit + تجديد session
            RateLimiter::clear($this->throttleKey());
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // ✨ فشل: زيادة عداد المحاولات
        RateLimiter::hit($this->throttleKey(), 60);   // expires in 60 seconds

        $this->errorMessage = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        $this->password = '';   // ✨ مسح كلمة المرور بعد الفشل
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
