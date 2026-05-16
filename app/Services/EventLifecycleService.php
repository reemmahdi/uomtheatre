<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 * EventLifecycleService — UOMTheatre (جديد)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 الهدف:
 *   إدارة دورة حياة الفعالية ما بعد الموافقة:
 *
 *   - publish()      ACTIVE → PUBLISHED  (مدير الإعلام يضغط "نشر")
 *   - cancel()       PUBLISHED/ACTIVE → CANCELLED  (إلغاء بسبب)
 *   - pauseBooking() إيقاف الحجز مؤقتاً (مع الإبقاء على الحالة)
 *   - resumeBooking() استئناف الحجز
 *
 * 🔄 سير الحياة الكامل:
 *
 *   DRAFT → ADDED → REJECTED → (تعديل) → ADDED → ...
 *                ↓
 *                ACTIVE → publish() → PUBLISHED → close → END
 *                                  ↓
 *                                  cancel() → CANCELLED
 *
 * 💡 الفرق بين CANCEL و REJECTED:
 *   - REJECTED: رفضها مكتب الرئاسة (قبل النشر)
 *   - CANCELLED: ألغاها مدير الإعلام (بعد النشر عادة)
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventLifecycleService
{
    // ════════════════════════════════════════════════════════════
    // النشر (Publish)
    // ════════════════════════════════════════════════════════════

    /**
     * نشر الفعالية للجمهور
     *
     * - الفعالية يجب أن تكون بحالة ACTIVE (وافقت الرئاسة)
     * - يسجّل published_at + published_by
     * - يرسل إشعارات (إن أمكن)
     */
    public function publish(Event $event, ?User $user = null): array
    {
        $user = $user ?? Auth::user();

        return DB::transaction(function () use ($event, $user) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            // التحقق من الجاهزية
            if (!$event->isReadyToPublish()) {
                return [
                    'success' => false,
                    'message' => 'الفعالية غير جاهزة للنشر (يجب أن تكون مقبولة من الرئاسة)',
                ];
            }

            $oldStatusId = $event->status_id;
            $publishedStatus = Status::where('name', Status::PUBLISHED)->firstOrFail();

            // تحديث الفعالية
            $event->update([
                'status_id'    => $publishedStatus->id,
                'published_at' => now(),
                'published_by' => $user?->id,
            ]);

            // سجل التغيير
            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => $user?->id ?? Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $publishedStatus->id,
            ]);

            // إشعار الجمهور والموظفين
            $this->safeNotify('notifyEventPublished', $event);

            return [
                'success' => true,
                'message' => 'تم نشر الفعالية للجمهور بنجاح',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // الإلغاء (Cancel)
    // ════════════════════════════════════════════════════════════

    /**
     * إلغاء الفعالية
     *
     * - يقبل الإلغاء من: ACTIVE, PUBLISHED, CLOSED
     * - يحفظ سبب الإلغاء وتاريخه
     * - يرسل إشعارات للحاجزين (إن أمكن)
     */
    public function cancel(Event $event, ?string $reason = null, ?User $user = null): array
    {
        $user = $user ?? Auth::user();

        return DB::transaction(function () use ($event, $reason, $user) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            $cancellableStatuses = [
                Status::ACTIVE,
                Status::PUBLISHED,
                Status::CLOSED,
            ];

            if (!in_array($event->status?->name, $cancellableStatuses, true)) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إلغاء الفعالية في حالتها الحالية',
                ];
            }

            $oldStatusId = $event->status_id;
            $cancelledStatus = Status::where('name', Status::CANCELLED)->firstOrFail();

            $event->update([
                'status_id'           => $cancelledStatus->id,
                'cancellation_reason' => $reason,
                'cancelled_at'        => now(),
            ]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => $user?->id ?? Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $cancelledStatus->id,
            ]);

            // إشعار الحاجزين والموظفين
            $this->safeNotify('notifyEventCancelled', $event, $reason);

            return [
                'success' => true,
                'message' => 'تم إلغاء الفعالية وإرسال الإشعارات',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // إيقاف/استئناف الحجز (Pause/Resume Booking)
    // ════════════════════════════════════════════════════════════

    /**
     * إيقاف الحجز مؤقتاً للفعالية (بدون تغيير الحالة)
     *
     * مفيد لـ:
     *   - تعديل وقت الفعالية مؤقتاً
     *   - مراجعة الحجوزات قبل الفعالية
     */
    public function pauseBooking(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            if (!$event->isPublished()) {
                return [
                    'success' => false,
                    'message' => 'يمكن إيقاف الحجز فقط للفعاليات المنشورة',
                ];
            }

            if ($event->is_booking_paused) {
                return [
                    'success' => false,
                    'message' => 'الحجز موقوف بالفعل',
                ];
            }

            $event->update([
                'is_booking_paused' => true,
                'paused_at'         => now(),
            ]);

            return [
                'success' => true,
                'message' => 'تم إيقاف الحجز مؤقتاً',
            ];
        });
    }

    /**
     * استئناف الحجز للفعالية
     */
    public function resumeBooking(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            if (!$event->is_booking_paused) {
                return [
                    'success' => false,
                    'message' => 'الحجز غير موقوف',
                ];
            }

            $event->update([
                'is_booking_paused' => false,
                'paused_at'         => null,
            ]);

            return [
                'success' => true,
                'message' => 'تم استئناف الحجز',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // Internal Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * استدعاء آمن لـ NotificationService
     */
    protected function safeNotify(string $method, ...$args): void
    {
        try {
            $service = app(NotificationService::class);
            if (method_exists($service, $method)) {
                $service->$method(...$args);
            }
        } catch (\Throwable $e) {
            \Log::warning("NotificationService::{$method} failed: " . $e->getMessage());
        }
    }
}
