<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ════════════════════════════════════════════════════════════════
 * CheckRole Middleware — UOMTheatre
 * ════════════════════════════════════════════════════════════════
 *
 * يحمي الـ routes على مستوى الدور قبل الوصول للـ Controller
 *
 * طريقة الاستخدام في routes/web.php:
 *
 *   Route::middleware(['admin.web', 'role:super_admin,theater_manager'])
 *       ->get('/dashboard/events', ...);
 *
 * ✨ التعديلات (إصلاحات Claude):
 *   🔴 nullsafe operator (?->) - الكود السابق كان يفشل لو role null
 *      $userRole = $user->role->name ?? null;  ❌ ينفجر قبل ??
 *      $userRole = $user->role?->name;          ✅
 *   - تحميل role إن لم يكن محمّلاً
 *   - in_array مع strict=true
 *   - دعم استخدامها في API routes أيضاً (request()->user())
 *
 * ════════════════════════════════════════════════════════════════
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // ✨ يدعم web (Auth::check) و API (request()->user())
        $user = Auth::user() ?? $request->user();

        if (!$user) {
            // API request → JSON | Web → redirect
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }
            return redirect()->route('login');
        }

        // التحقق من تفعيل الحساب
        if (!$user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'حسابك معطّل'], 403);
            }
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'حسابك معطّل. يرجى التواصل مع الإدارة');
        }

        // ✨ تحميل role إن لم يكن محمّلاً
        $user->loadMissing('role');

        // ✨ مُصحَّح: nullsafe + in_array strict
        $userRole = $user->role?->name;

        if (!$userRole || !in_array($userRole, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'ليست لديك صلاحية لهذه الصفحة',
                ], 403);
            }
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
