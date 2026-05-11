<?php

use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes — UOMTheatre (مُحدّث - إصلاحات Claude)
|--------------------------------------------------------------------------
|
| ✨ التعديلات:
|   - إضافة scheduled task لإنهاء الفعاليات التي تجاوز end_datetime
|   - (ينقل المنطق من Events.php Livewire — كان يُنفّذ في كل render!)
|
| لتشغيل الـ scheduler يدوياً (للاختبار):
|   php artisan schedule:run
|
| لتشغيله تلقائياً (production):
|   - على Linux: cron job كل دقيقة:
|     * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|   - على Windows (XAMPP محلي): استخدمي Task Scheduler
|   - على Laravel Cloud: مفعّل تلقائياً
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✨ جديد: إنهاء الفعاليات التي تجاوزت end_datetime
Artisan::command('events:auto-end', function () {
    $endStatus = Status::where('name', Status::END)->first();
    if (!$endStatus) {
        $this->error('Status::END غير موجود في DB');
        return 1;
    }

    $expiredEvents = Event::where('end_datetime', '<', now())
        ->whereHas('status', fn($q) => $q->whereIn('name', [Status::ACTIVE, Status::PUBLISHED]))
        ->get(['id', 'status_id', 'title']);

    if ($expiredEvents->isEmpty()) {
        $this->info('✓ لا توجد فعاليات منتهية');
        return 0;
    }

    foreach ($expiredEvents as $event) {
        $oldStatusId = $event->status_id;

        $event->update([
            'status_id'         => $endStatus->id,
            'is_booking_paused' => false,
            'paused_at'         => null,
        ]);

        EventLog::create([
            'event_id'      => $event->id,
            'user_id'       => null,   // ✨ null = نظامي (مش user محدد)
            'old_status_id' => $oldStatusId,
            'new_status_id' => $endStatus->id,
        ]);

        $this->info("✓ أُنهيت: {$event->title}");
    }

    $this->info("📊 الإجمالي: {$expiredEvents->count()} فعالية");
    return 0;
})->purpose('Auto-end expired events (active/published → end)');

// ⏰ تشغيل تلقائي كل 15 دقيقة
Schedule::command('events:auto-end')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
