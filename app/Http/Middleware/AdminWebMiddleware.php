<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ════════════════════════════════════════════════════════════════
 * AdminWebMiddleware — UOMTheatre (Web)
 * ════════════════════════════════════════════════════════════════
 *
 * يحمي مسارات لوحة التحكم على الويب.
 * يُستخدم في routes/web.php كـ middleware('admin.web')
 *
 * ✨ التعديلات (إصلاحات Claude):
 *   🔴 nullsafe operator (?->) - منع crash لو role null
 *   - تحميل role قبل الفحص (eager)
 *   - استخدام Role::USER constant
 *   - Return type hint
 *
 * ════════════════════════════════════════════════════════════════
 */
class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'يجب تسجيل الدخول أولاً');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'حسابك معطّل');
        }

        // ✨ تحميل role إن لم يكن محمّلاً (تجنّب N+1 وتأمين nullsafe)
        $user->loadMissing('role');

        // ✨ مُصحَّح: nullsafe (?->) + استخدام constant
        // لو user بدون role صحيح، نسجّل خروج (بدل crash)
        if (!$user->role || $user->role->name === Role::USER) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'لوحة التحكم مخصصة للموظفين');
        }

        return $next($request);
    }
}
