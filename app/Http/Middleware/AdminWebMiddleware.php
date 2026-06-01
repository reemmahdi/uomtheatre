<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

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

        $user->loadMissing('role');

        if (!$user->role || $user->role->name === Role::USER) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'لوحة التحكم مخصصة للموظفين');
        }

        return $next($request);
    }
}
