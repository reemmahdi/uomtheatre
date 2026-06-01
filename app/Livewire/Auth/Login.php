<?php

namespace App\Livewire\Auth;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public string $errorMessage = '';

    protected function throttleKey(): string
    {
        return Str::lower($this->email) . '|' . request()->ip();
    }

    public function login()
    {
        $this->errorMessage = '';

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->errorMessage = "محاولات كثيرة. حاولي مرة أخرى بعد {$seconds} ثانية";
            $this->password = '';
            return;
        }

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

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $this->errorMessage = 'حسابك معطّل. يرجى التواصل مع الإدارة';
                $this->password = '';
                return;
            }

            if ($user->role?->name === Role::USER) {
                Auth::logout();
                $this->errorMessage = 'لوحة التحكم مخصصة للموظفين. استخدمي التطبيق للحجز';
                $this->password = '';
                return;
            }

            RateLimiter::clear($this->throttleKey());
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($this->throttleKey(), 60);

        $this->errorMessage = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        $this->password = '';
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
