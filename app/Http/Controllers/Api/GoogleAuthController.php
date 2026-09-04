<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Google\Auth\AccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $payload = (new AccessToken())->verify($request->id_token, [
                'audience' => config('services.google.client_id'),
            ]);
        } catch (\Throwable $e) {
            $payload = false;
        }

        $validIssuer = in_array($payload['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
        if (!is_array($payload) || !$validIssuer || empty($payload['sub']) || empty($payload['email'])) {
            return response()->json([
                'message' => 'رمز دخول غير صالح لهذا التطبيق',
            ], 401);
        }

        $allowedClients = config('services.google.allowed_client_ids', []);
        $azp = $payload['azp'] ?? null;
        if ($allowedClients !== [] && ($azp === null || !in_array($azp, $allowedClients, true))) {
            return response()->json([
                'message' => 'رمز دخول غير صالح لهذا التطبيق',
            ], 401);
        }

        if (($payload['email_verified'] ?? false) !== true) {
            return response()->json([
                'message' => 'بريد حساب كوكل غير موثق',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email    = $payload['email'];
        $name     = $payload['name'] ?? '';
        $avatar   = $payload['picture'] ?? null;

        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            $user->update(['avatar' => $avatar ?? $user->avatar]);
        } else {
            $byEmail = User::with('role')->where('email', $email)->first();
            if ($byEmail) {
                if ($byEmail->google_id !== null || $byEmail->role?->name !== Role::USER) {
                    return response()->json([
                        'message' => 'هذا البريد مرتبط بحساب آخر — راجع إدارة القاعة',
                    ], 403);
                }
                $byEmail->update([
                    'google_id' => $googleId,
                    'avatar'    => $avatar ?? $byEmail->avatar,
                ]);
                $user = $byEmail;
            } else {
                $user = User::create([
                    'name'      => $name,
                    'email'     => $email,
                    'phone'     => '',
                    'google_id' => $googleId,
                    'avatar'    => $avatar,
                    'password'  => Hash::make(Str::random(40)),
                    'is_active' => true,
                    'role_id'   => Role::where('name', Role::USER)->value('id'),
                ]);
            }
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'حسابك معطل — راجع إدارة القاعة',
            ], 403);
        }

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

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
