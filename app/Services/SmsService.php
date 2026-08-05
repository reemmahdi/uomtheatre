<?php

namespace App\Services;

use Twilio\Rest\Client;

/**
 * إرسال رسائل SMS عبر Twilio.
 * بلا مفاتيح في .env تعيد رسالة "غير مهيأة" بدل أن تفشل —
 * فالنظام يعمل كاملاً ويُفعَّل الإرسال يوم يتوفر حساب مدفوع.
 */
class SmsService
{
    /** @return array{success: bool, message: string} */
    public function send(string $phone, string $message): array
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            return [
                'success' => false,
                'message' => 'خدمة الرسائل غير مهيأة بعد — تحتاج مفاتيح Twilio في إعدادات الخادم',
            ];
        }

        // توحيد الرقم العراقي للصيغة الدولية: 07x → +9647x
        $to = preg_replace('/[^0-9]/', '', $phone);
        if ($to === '' || strlen($to) < 10) {
            return ['success' => false, 'message' => 'رقم الجوال غير صالح'];
        }
        if (str_starts_with($to, '0')) {
            $to = '964' . substr($to, 1);
        }
        $to = '+' . $to;

        try {
            (new Client($sid, $token))->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);
            return ['success' => true, 'message' => "أُرسلت الرسالة إلى {$to}"];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'فشل الإرسال: ' . $e->getMessage()];
        }
    }
}
