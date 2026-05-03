<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public string $errorMessage = '';

    public function login()
    {
        // مسح أي رسالة خطأ سابقة
        $this->errorMessage = '';

        // التحقق من البيانات - نستخدم try/catch لتوحيد الرسائل في مكان واحد
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
            // عرض أول خطأ فقط في صندوق الرسالة الموحّد
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
                return;
            }

            // منع المستخدمين العاديين من الدخول للوحة التحكم
            if ($user->role->name === 'user') {
                Auth::logout();
                $this->errorMessage = 'لوحة التحكم مخصصة للموظفين. استخدم التطبيق للحجز';
                return;
            }

            // تجديد الـ session لمنع Session Fixation (أمان)
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        $this->errorMessage = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
