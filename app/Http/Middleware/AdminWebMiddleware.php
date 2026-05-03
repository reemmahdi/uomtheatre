<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'حسابك معطّل');
        }

        if (Auth::user()->role->name === 'user') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'لوحة التحكم مخصصة للموظفين');
        }

        return $next($request);
    }
}
