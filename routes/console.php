<?php

use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
            'user_id'       => null,
            'old_status_id' => $oldStatusId,
            'new_status_id' => $endStatus->id,
        ]);

        $this->info("✓ أُنهيت: {$event->title}");
    }

    $this->info("📊 الإجمالي: {$expiredEvents->count()} فعالية");
    return 0;
})->purpose('Auto-end expired events (active/published → end)');

Schedule::command('events:auto-end')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
