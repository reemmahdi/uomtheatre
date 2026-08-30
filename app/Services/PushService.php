<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PushService
{
    public function sendToUser(?User $user, string $title, string $body): bool
    {
        if (!$user || !$user->fcm_token) {
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

            $message = CloudMessage::fromArray([
                'token' => $user->fcm_token,
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
            ]);

            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::warning('push: ' . $e->getMessage());
            return false;
        }
    }
}
