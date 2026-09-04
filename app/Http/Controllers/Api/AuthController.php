<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['البريد أو كلمة المرور غير صحيحة'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'حسابك معطّل. تواصل مع الإدارة',
            ], 403);
        }

        $abilities = $user->isAdmin() ? ['staff'] : ['mobile'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user'    => $user->load('role'),
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $deviceToken = $request->input('device_token');
        if (is_string($deviceToken) && $deviceToken !== '') {
            $user->deviceTokens()->where('token', $deviceToken)->delete();
            if ($user->fcm_token === $deviceToken) {
                $user->forceFill(['fcm_token' => null])->save();
            }
        }
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role'),
        ]);
    }
}
