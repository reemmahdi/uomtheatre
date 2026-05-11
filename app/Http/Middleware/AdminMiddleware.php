<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ════════════════════════════════════════════════════════════════
 * AdminMiddleware — UOMTheatre (API)
 * ════════════════════════════════════════════════════════════════
 *
 * يسمح بالمرور فقط للمستخدمين الإداريين (أي مستخدم ليس role=user).
 * يُستخدم مع مسارات API المحمية عبر Sanctum.
 *
 * ✨ التعديلات (إصلاحات Claude):
 *   - استخدام Role::USER constant بدل string literal
 *   - Return type hint
 *   - تحسين رسائل الخطأ
 *
 * ════════════════════════════════════════════════════════════════
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح - يجب تسجيل الدخول',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'حسابك معطّل. تواصل مع الإدارة',
            ], 403);
        }

        // تحميل الدور إن لم يكن محمّلاً
        $user->loadMissing('role');

        // ✨ مُحسَّن: استخدام Role::USER constant
        if (!$user->role || $user->role->name === Role::USER) {
            return response()->json([
                'message' => 'ليست لديك صلاحية للوصول إلى هذا المورد',
            ], 403);
        }

        return $next($request);
    }
}
