<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventScheduleChange;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * تأجيل فعالية منشورة عليها حجوزات — بمعاملة واحدة:
 * يوثق التغيير، يحدث الموعد، يمهل كل حاجز 24 ساعة للتأكيد،
 * ويشعر الجميع بالموعد الجديد.
 *
 * القواعد:
 * - المقعد يبقى محجوزاً لصاحبه طول المهلة (لا إلغاء عرضي)
 * - المهلة = 24 ساعة كاملة (قرار اللجنة)
 * - حجوزات ضيوف الشرف (vip_guest) لا تدخل دورة التأكيد —
 *   أصحابها بلا حسابات، وتبقى مثبتة تُدار من اللوحة
 * - تأجيل فوق تأجيل: المهلة تُعاد من جديد ويُشعر الجميع مجدداً
 */
class EventScheduleChangeService
{
    /** @return array{change: EventScheduleChange, notified: int} */
    public function postpone(
        Event $event,
        Carbon $newStart,
        Carbon $newEnd,
        ?string $reason,
        User $admin,
    ): array {
        return DB::transaction(function () use ($event, $newStart, $newEnd, $reason, $admin) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            // 1) التوثيق: سجل التغيير
            $change = EventScheduleChange::create([
                'event_id'           => $event->id,
                'old_start_datetime' => $event->start_datetime,
                'old_end_datetime'   => $event->end_datetime,
                'new_start_datetime' => $newStart,
                'new_end_datetime'   => $newEnd,
                'reason'             => $reason,
                'changed_by'         => $admin->id,
            ]);

            // 2) تحديث موعد الفعالية
            $event->update([
                'start_datetime' => $newStart,
                'end_datetime'   => $newEnd,
            ]);

            // 3) مهلة التأكيد: 24 ساعة كاملة (قرار اللجنة)
            $deadline = now()->addHours(24);

            $reservations = Reservation::with(['user', 'seat'])
                ->where('event_id', $event->id)
                ->where('status', '!=', 'cancelled')
                ->where('type', '!=', 'vip_guest')
                ->whereNotNull('user_id')
                ->lockForUpdate()
                ->get();

            $when = self::formatArabic($newStart);
            $reasonText = $reason ? "\n\nالسبب: {$reason}" : '';

            foreach ($reservations as $reservation) {
                $reservation->update([
                    'schedule_change_id'  => $change->id,
                    'confirm_until'       => $deadline,
                    'change_confirmed_at' => null, // تأجيل جديد = تأكيد جديد
                ]);

                Notification::create([
                    'user_id'  => $reservation->user_id,
                    'title'    => '📅 تغيير موعد الفعالية',
                    'message'  => "تم تغيير موعد فعالية \"{$event->title}\" إلى {$when}.{$reasonText}\n\n"
                        . "مقعدك ({$reservation->seat?->label}) ما زال محجوزاً لك — "
                        . "يرجى تأكيد حجزك خلال 24 ساعة من صفحة تذاكري، "
                        . "وإلا سيُلغى تلقائياً ويصبح المقعد متاحاً للآخرين.",
                    'type'     => 'schedule_change',
                    'event_id' => $event->id,
                    'is_read'  => false,
                ]);
            }

            return ['change' => $change, 'notified' => $reservations->count()];
        });
    }

    /** صياغة الموعد بالعربية: الأربعاء 30 تموز 2026 — 10:00 صباحاً */
    public static function formatArabic(Carbon $dt): string
    {
        $months = [1 => 'كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران',
            'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'];
        $days = ['Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت', 'Sunday' => 'الأحد'];
        $period = $dt->hour < 12 ? 'صباحاً' : 'مساءً';
        $h = $dt->hour % 12 ?: 12;
        return $days[$dt->format('l')] . ' ' . $dt->day . ' ' . $months[$dt->month]
            . ' ' . $dt->year . ' — ' . $h . ':' . $dt->format('i') . ' ' . $period;
    }
}
