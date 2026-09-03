<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && !$user->is_active) {
            $request->user()->currentAccessToken()?->delete();
            return response()->json(['message' => 'حسابك معطّل. تواصل مع الإدارة'], 403);
        }
        return $next($request);
    }
}
