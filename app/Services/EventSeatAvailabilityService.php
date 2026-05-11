<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSeatAvailability;
use App\Models\Seat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ════════════════════════════════════════════════════════════════
 * EventSeatAvailabilityService — UOMTheatre (مُحدّث)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات في هذه النسخة (إصلاحات Claude):
 *   🔴 إصلاح bug في toggleSeat: المنطق المعكوس لـ exclusion_reason
 *      كان يحفظ reason للمقاعد المتاحة ويمحيه من المستبعدة!
 *   🔴 إصلاح bug في bulkUpdate: UUID كان يُولّد في كل update
 *      → الآن يستخدم HasUuid trait فقط للسجلات الجديدة
 *   🟡 إضافة DB::transaction + lockForUpdate في initializeForEvent
 *   🟡 use Str في الأعلى بدل full namespace كل مرة
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventSeatAvailabilityService
{
    // ════════════════════════════════════════════════════════════
    // التهيئة الأولى لفعالية جديدة
    // ════════════════════════════════════════════════════════════

    /**
     * إنشاء سجل لكل مقعد في المسرح بحالة "متاح"
     * تُستدعى أول مرة عند فتح شاشة تحديد المقاعد.
     *
     * ✨ مُحسّن: thread-safe عبر transaction
     */
    public function initializeForEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            // التحقق ما إذا كانت السجلات موجودة بالفعل (داخل transaction للأمان)
            $existingCount = EventSeatAvailability::where('event_id', $event->id)
                ->lockForUpdate()
                ->count();

            if ($existingCount > 0) {
                return 0; // تم التهيئة من قبل
            }

            $now = now();
            $userId = Auth::id();

            // جلب كل المقاعد
            $seats = Seat::orderBy('id')->get();

            $records = [];
            foreach ($seats as $seat) {
                $records[] = [
                    'uuid'                => (string) Str::uuid(),
                    'event_id'            => $event->id,
                    'seat_id'             => $seat->id,
                    // ✅ كل المقاعد تبدأ متاحة - مدير الإعلام يستبعد ما يريد لاحقاً
                    'is_public_available' => true,
                    'exclusion_reason'    => null,
                    'updated_by'          => $userId,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }

            // إدراج دفعة واحدة (أسرع بكثير من حلقة create)
            DB::table('event_seat_availability')->insert($records);

            return count($records);
        });
    }

    // ════════════════════════════════════════════════════════════
    // تبديل حالة مقعد واحد
    // ════════════════════════════════════════════════════════════

    /**
     * تبديل إتاحة مقعد (متاح ↔ مستبعد)
     *
     * 🔴 مُصحَّح: المنطق السابق كان يحفظ reason في المتاحة (عكس!)
     */
    public function toggleSeat(Event $event, int $seatId, ?string $reason = null): EventSeatAvailability
    {
        $record = EventSeatAvailability::firstOrCreate(
            [
                'event_id' => $event->id,
                'seat_id'  => $seatId,
            ],
            [
                'is_public_available' => true,
                'updated_by'          => Auth::id(),
            ]
        );

        // ✨ نحسب القيمة الجديدة في متغيّر منفصل (لتجنّب bug المنطق المعكوس)
        $newAvailability = !$record->is_public_available;

        $record->update([
            'is_public_available' => $newAvailability,
            // ✅ المنطق الصحيح: لو متاح → no reason | لو مستبعد → use reason
            'exclusion_reason'    => $newAvailability ? null : $reason,
            'updated_by'          => Auth::id(),
        ]);

        return $record;
    }

    // ════════════════════════════════════════════════════════════
    // تحديث جماعي
    // ════════════════════════════════════════════════════════════

    /**
     * تحديث عدة مقاعد دفعة واحدة
     *
     * 🔴 مُصحَّح: لا نولّد UUID في كل update (يكسر IDOR security)
     *    HasUuid trait يولّده تلقائياً عند الإنشاء فقط.
     */
    public function bulkUpdate(Event $event, array $seatIds, bool $available, ?string $reason = null): int
    {
        if (empty($seatIds)) {
            return 0;
        }

        $userId = Auth::id();
        $count  = 0;

        DB::transaction(function () use ($event, $seatIds, $available, $reason, $userId, &$count) {
            foreach ($seatIds as $seatId) {
                EventSeatAvailability::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'seat_id'  => (int) $seatId,
                    ],
                    [
                        // ✅ لا uuid هنا — HasUuid trait يتولّى الإنشاء فقط
                        'is_public_available' => $available,
                        'exclusion_reason'    => $available ? null : $reason,
                        'updated_by'          => $userId,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    /**
     * تحديد كل المقاعد كمتاحة (إعادة تعيين)
     */
    public function makeAllAvailable(Event $event): int
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->update([
                'is_public_available' => true,
                'exclusion_reason'    => null,
                'updated_by'          => Auth::id(),
                'updated_at'          => now(),
            ]);
    }

    /**
     * استبعاد كل مقاعد قسم معيّن
     */
    public function excludeSection(Event $event, int $sectionId, ?string $reason = null): int
    {
        $seatIds = Seat::where('section_id', $sectionId)->pluck('id')->toArray();
        return $this->bulkUpdate($event, $seatIds, false, $reason);
    }

    /**
     * إتاحة كل مقاعد قسم معيّن
     */
    public function includeSection(Event $event, int $sectionId): int
    {
        $seatIds = Seat::where('section_id', $sectionId)->pluck('id')->toArray();
        return $this->bulkUpdate($event, $seatIds, true);
    }

    // ════════════════════════════════════════════════════════════
    // Queries
    // ════════════════════════════════════════════════════════════

    public function getAvailableSeatIds(Event $event): array
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->where('is_public_available', true)
            ->pluck('seat_id')
            ->toArray();
    }

    public function getExcludedSeatIds(Event $event): array
    {
        return EventSeatAvailability::where('event_id', $event->id)
            ->where('is_public_available', false)
            ->pluck('seat_id')
            ->toArray();
    }

    /**
     * إحصائيات سريعة (query واحد بدل اثنين)
     */
    public function getStats(Event $event): array
    {
        $stats = EventSeatAvailability::where('event_id', $event->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_public_available = ? THEN 1 ELSE 0 END) as available
            ', [true])
            ->first();

        $total     = (int) ($stats->total ?? 0);
        $available = (int) ($stats->available ?? 0);

        return [
            'total'     => $total,
            'available' => $available,
            'excluded'  => $total - $available,
        ];
    }

    public function isInitialized(Event $event): bool
    {
        return EventSeatAvailability::where('event_id', $event->id)->exists();
    }
}
