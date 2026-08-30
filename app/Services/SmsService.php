<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    private const BASE = 'https://otp.arqam.tech/api';

    /** @return array{success: bool, message: string} */
    public function send(string $phone, string $message): array
    {
        if (!config('services.arqam.key')) {
            return [
                'success' => false,
                'message' => 'خدمة الرسائل غير مهيأة — يحتاج مفتاح أرقام في إعدادات الخادم',
            ];
        }

        return [
            'success' => false,
            'message' => 'رسائل الدعوات تتفعل فور اعتماد قالب الدعوة (Utility) في منصة أرقام',
        ];
    }

    /** @return array{success: bool, message: string} */
    public function sendOtp(string $phone): array
    {
        $key = config('services.arqam.key');
        if (!$key) {
            return [
                'success' => false,
                'message' => 'خدمة الرسائل غير مهيأة — يحتاج مفتاح أرقام في إعدادات الخادم',
            ];
        }

        $to = $this->normalize($phone);
        if ($to === null) {
            return ['success' => false, 'message' => 'رقم الجوال غير صالح'];
        }

        try {
            $res = Http::withHeaders(['X-API-Key' => $key])
                ->timeout(15)
                ->post(self::BASE . '/sms/otp', [
                    'phoneNumber' => $to,
                ]);

            $data = $res->json() ?? [];

            if ($res->successful() && ($data['success'] ?? false)) {
                $channel = $data['channel'] ?? 'whatsapp';
                return [
                    'success' => true,
                    'message' => "أُرسلت رسالة تحقق إلى {$to} عبر {$channel}"
                        . (isset($data['cost']) ? " (الكلفة: {$data['cost']})" : ''),
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل الإرسال: '
                    . ($data['code'] ?? $data['message'] ?? ('HTTP ' . $res->status())),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'تعذر الاتصال بمنصة أرقام: ' . $e->getMessage()];
        }
    }

    /** @return array{success: bool, message: string} */
    public function account(): array
    {
        $key = config('services.arqam.key');
        if (!$key) {
            return ['success' => false, 'message' => 'خدمة الرسائل غير مهيأة'];
        }

        try {
            $res = Http::withHeaders(['X-API-Key' => $key])
                ->timeout(15)
                ->get(self::BASE . '/sms/account');

            $data = $res->json() ?? [];
            if ($res->successful() && ($data['success'] ?? false)) {
                $balance = $data['balance']['available'] ?? '؟';
                $currency = $data['balance']['currency'] ?? '';
                $status = $data['account']['status'] ?? '؟';
                return [
                    'success' => true,
                    'message' => "الحساب: {$status} — الرصيد المتاح: {$balance} {$currency}",
                ];
            }
            return ['success' => false, 'message' => 'HTTP ' . $res->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'تعذر الاتصال: ' . $e->getMessage()];
        }
    }

    private function normalize(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '' || strlen($digits) < 10) {
            return null;
        }
        if (str_starts_with($digits, '964')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+964' . substr($digits, 1);
        }
        return '+964' . $digits;
    }
}
