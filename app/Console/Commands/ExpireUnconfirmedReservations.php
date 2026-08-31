<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnconfirmedReservations extends Command
{
    protected $signature = 'reservations:expire-unconfirmed';
    protected $description = 'إلغاء الحجوزات غير المؤكدة بعد انتهاء مهلة تغيير الموعد';

    public function handle(): int
    {
        $expired = 0;

        DB::transaction(function () use (&$expired) {
            $reservations = Reservation::with(['event', 'seat'])
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('confirm_until')
                ->whereNull('change_confirmed_at')
                ->where('confirm_until', '<', now())
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->update(['status' => 'cancelled']);

                Notification::create([
                    'user_id'  => $reservation->user_id,
                    'title'    => '⚠️ إلغاء حجز لعدم التأكيد',
                    'message'  => "أُلغي حجزك لمقعد {$reservation->seat?->label} في "
                        . "فعالية \"{$reservation->event?->title}\" لعدم تأكيده خلال المهلة "
                        . "بعد تغيير الموعد.\n\nالمقعد أصبح متاحاً — يمكنك الحجز مجدداً "
                        . "ما دامت المقاعد متوفرة.",
                    'type'     => 'reservation_cancelled',
                    'event_id' => $reservation->event_id,
                    'is_read'  => false,
                ]);
                $expired++;
            }
        });

        $this->info("Expired: {$expired}");
        return self::SUCCESS;
    }
}
