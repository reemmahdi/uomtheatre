#!/bin/bash
cd "$(dirname "$0")"

echo "======= فحص التسليم الشامل ======="

echo "--- 1) سلامة PHP نحويا ---"
ERR=0
for f in $(find app routes config database/migrations -name '*.php' 2>/dev/null); do
    php -l "$f" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "X خطأ نحوي: $f"
        ERR=1
    fi
done
if [ $ERR -eq 0 ]; then echo "OK كل الملفات سليمة نحويا"; fi

echo "--- 2) المسارات ---"
if php artisan route:list > /dev/null 2>&1; then
    echo "OK المسارات تُحمل بلا أخطاء"
else
    echo "X خطأ بتحميل المسارات"
fi

echo "--- 3) ثغرات الحزم ---"
composer audit 2>/dev/null | tail -1

echo "--- 4) الحمايات المفصلية ---"
check() {
    if grep -q "$1" "$2" 2>/dev/null; then
        echo "OK $3"
    else
        echo "X $3 -- مفقود"
    fi
}
check "throttle:5,1" routes/api.php "throttle الدخول"
check "throttle:60,1" routes/web.php "throttle مسارات الإدارة"
check "is_active" app/Http/Controllers/Api/GoogleAuthController.php "حجب الحساب المعطل - كوكل"
check "isProduction" app/Providers/AppServiceProvider.php "فرض https بالإنتاج"

echo "--- 5) منظومة الإشعارات ---"
check "booted" app/Models/Notification.php "شرارة الدفع بموديل الإشعار"
check "fromArray" app/Services/PushService.php "خدمة الدفع بالصيغة الحديثة"
if php artisan route:list --path=device-token 2>/dev/null | grep -q POST; then
    echo "OK مسار تسجيل التوكن"
else
    echo "X مسار device-token مفقود"
fi

echo "--- 6) منظومة أرقام ---"
check "sendInvitation" app/Services/SmsService.php "خدمة الدعوات"
check "sendInvitation" app/Livewire/Dashboard/VipGuests.php "زر الإرسال موصول"
check "bodyParameters" app/Services/SmsService.php "صيغة أرقام الصحيحة"
if grep -qi "whatsapp" resources/views/livewire/dashboard/vip-guests.blade.php 2>/dev/null; then
    echo "X بقايا واتساب مجاني"
else
    echo "OK الواتساب المجاني مزال"
fi

echo "--- 7) الحجز والفحص ---"
check "cancelled" app/Livewire/Dashboard/VipBooking.php "منطق الحجز الملغى"
check "subHours" app/Livewire/Dashboard/CheckIn.php "بوابة الساعتين"
check "is_public_available" app/Http/Controllers/Api/SeatsApiController.php "شاشة العرض v2"

echo "--- 8) الأسرار خارج git ---"
if git check-ignore .env > /dev/null 2>&1; then echo "OK .env مستثنى"; else echo "X .env داخل git"; fi
if git check-ignore storage/app/firebase/firebase-credentials.json > /dev/null 2>&1; then
    echo "OK مفتاح فايربيس مستثنى"
else
    echo "X مفتاح فايربيس مكشوف"
fi

echo "--- 9) التعليقات ---"
C=$(grep -rn "// " app/ --include="*.php" 2>/dev/null | wc -l | tr -d ' ')
echo "تعليقات متبقية داخل app: $C"

echo "======= نهاية الفحص ======="
