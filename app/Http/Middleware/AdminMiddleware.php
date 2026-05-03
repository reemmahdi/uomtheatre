<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * يسمح بالمرور فقط للمستخدمين الإداريين (أي مستخدم ليس role=user).
     * يُستخدم مع مسارات API المحمية عبر Sanctum.
     */
    public function handle(Request $request, Closure $next)
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

        if (!$user->role || $user->role->name === 'user') {
            return response()->json([
                'message' => 'ليست لديك صلاحية للوصول إلى هذا المورد',
            ], 403);
        }

        return $next($request);
    }
}
