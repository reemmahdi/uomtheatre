<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user() ?? $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'حسابك معطّل'], 403);
            }
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'حسابك معطّل. يرجى التواصل مع الإدارة');
        }

        $user->loadMissing('role');

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
