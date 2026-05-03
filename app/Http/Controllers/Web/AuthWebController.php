<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('dashboard.index');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        // تحقق: موجود + كلمة المرور صحيحة
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'البريد أو كلمة المرور غير صحيحة']);
        }

        // تحقق: الحساب فعال
        if (!$user->is_active) {
            return back()->withErrors(['email' => 'حسابك معطّل. تواصل مع الإدارة']);
        }

        // تحقق: لازم يكون دور إداري (أي دور غير "مستخدم عادي")
        if (!$user->isAdmin()) {
            return back()->withErrors(['email' => 'لوحة التحكم مخصصة للموظفين. استخدم التطبيق للحجز.']);
        }

        // حفظ بيانات الجلسة
        session([
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'user_email'=> $user->email,
            'user_role' => $user->role->display_name,
            'role_name' => $user->role->name,
        ]);

        return redirect()->route('dashboard.index')
            ->with('success', 'مرحباً ' . $user->name . ' (' . $user->role->display_name . ')');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('dashboard.login')
            ->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
