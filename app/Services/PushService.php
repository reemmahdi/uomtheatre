<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
class PushService
{
    public function sendToUser(?User $user, string $title, string $body): bool
    {
        if (!$user) {
            return false;
        }
        $tokens = $user->deviceTokens()->pluck('token');
        if ($user->fcm_token) {
            $tokens->push($user->fcm_token);
        }
        $tokens = $tokens->unique()->values();
        if ($tokens->isEmpty()) {
            return false;
        }
        $credentials = storage_path('app/firebase/firebase-credentials.json');
        if (!is_file($credentials)) {
            return false;
        }
        try {
            $messaging = (new Factory)
                ->withServiceAccount($credentials)
                ->createMessaging();
        } catch (\Throwable $e) {
            Log::warning('push: ' . $e->getMessage());
            return false;
        }
        $sent = 0;
        foreach ($tokens as $token) {
            try {
                $messaging->send(CloudMessage::fromArray([
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'uom_high',
                            'default_sound' => true,
                        ],
                    ],
                ]));
                $sent++;
            } catch (NotFound $e) {
                $user->deviceTokens()->where('token', $token)->delete();
            } catch (\Throwable $e) {
                Log::warning('push: ' . $e->getMessage());
            }
        }
        return $sent > 0;
    }
}
