<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================
 * CheckRole Middleware - UOMTheatre
 * ============================================================
 * يحمي الـ routes على مستوى الدور قبل الوصول للـ Controller
 *
 * طريقة الاستخدام في routes/web.php:
 *
 *   Route::middleware(['auth', 'role:super_admin,theater_manager'])
 *       ->get('/dashboard/events', ...);
 *
 * ============================================================
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // التحقق من تفعيل الحساب
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'حسابك معطّل. يرجى التواصل مع الإدارة');
        }

        // التحقق من الدور
        $userRole = $user->role->name ?? null;

        if (!in_array($userRole, $roles)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
