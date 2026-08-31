<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $response = Http::timeout(10)->get(
            'https://oauth2.googleapis.com/tokeninfo',
            ['id_token' => $request->id_token]
        );

        if (!$response->ok()) {
            return response()->json([
                'message' => 'تعذر التحقق من حساب كوكل — أعد المحاولة',
            ], 401);
        }

        $payload = $response->json();

        if (($payload['aud'] ?? null) !== config('services.google.client_id')) {
            return response()->json([
                'message' => 'رمز دخول غير صالح لهذا التطبيق',
            ], 401);
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            return response()->json([
                'message' => 'بريد حساب كوكل غير موثق',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email    = $payload['email'];
        $name     = $payload['name'] ?? '';
        $avatar   = $payload['picture'] ?? null;

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {

            $user->update([
                'google_id' => $googleId,
                'avatar'    => $avatar ?? $user->avatar,
            ]);
        } else {
            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'phone'     => '',
                'google_id' => $googleId,
                'avatar'    => $avatar,

                'password'  => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'حسابك موقوف — يرجى مراجعة إدارة النظام',
            ], 403);
        }
if (!$user->is_active) {
            return response()->json([
                'message' => 'حسابك معطل — راجع إدارة القاعة',
            ], 403);
        }
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'phone'  => $user->phone,
                'avatar' => $user->avatar,
            ],

            'needs_profile' => blank($user->phone),
        ]);
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([

            'name'  => ['required', 'string', 'max:255', 'regex:/^\S{2,}\s+\S{2,}\s+\S{2,}/u'],
            'phone' => ['required', 'string', 'regex:/^07\d{9}$/'],
        ], [
            'name.regex'  => 'يرجى إدخال الاسم الثلاثي كاملاً',
            'phone.regex' => 'رقم الهاتف يجب أن يكون 11 رقماً ويبدأ بـ 07',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'تم إكمال حسابك بنجاح',
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'phone'  => $user->phone,
                'avatar' => $user->avatar,
            ],
        ]);
    }
}
