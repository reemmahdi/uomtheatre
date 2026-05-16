<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\EventLog;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 * EventApprovalService — UOMTheatre (تصميم جديد)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 التصميم الجديد:
 *   - موافق واحد فقط (مكتب رئاسة الجامعة)
 *   - مدير المسرح يشاهد الفعالية فقط (بدون قرار)
 *   - السجل ينشأ فقط عند اتخاذ القرار
 *   - دعم دورات إعادة الإرسال (round_number) تتزايد تلقائياً
 *
 * 🔄 سير العمل:
 *
 *   مدير الإعلام:
 *     sendForApproval()  → DRAFT/REJECTED → ADDED
 *
 *   مكتب الرئاسة:
 *     approve()  → ADDED → ACTIVE (تنتظر زر النشر)
 *     reject()   → ADDED → REJECTED (سبب اختياري)
 *
 *   مدير الإعلام (بعد الرفض):
 *     resubmit() = sendForApproval() مرة ثانية → round++
 *
 * 🔒 Thread-safety:
 *   lockForUpdate لتجنّب race conditions عند الموافقة المتزامنة
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventApprovalService
{
    // ════════════════════════════════════════════════════════════
    // إرسال فعالية للموافقة (أو إعادة إرسال بعد رفض)
    // ════════════════════════════════════════════════════════════

    /**
     * إرسال الفعالية لمكتب الرئاسة للموافقة
     *
     * يقبل الفعاليات من حالة DRAFT (أول إرسال) أو REJECTED (إعادة إرسال)
     */
    public function sendForApproval(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            // يجوز الإرسال من draft (أول مرة) أو rejected (resubmit)
            $allowedFromStatuses = [Status::DRAFT, Status::REJECTED];
            if (!in_array($event->status?->name, $allowedFromStatuses, true)) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إرسال الفعالية في حالتها الحالية',
                ];
            }

            // تحديث حالة الفعالية إلى "added"
            $oldStatusId = $event->status_id;
            $addedStatus = Status::where('name', Status::ADDED)->firstOrFail();
            $event->update(['status_id' => $addedStatus->id]);

            // تسجيل تغيير الحالة
            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $addedStatus->id,
            ]);

            // ✨ مُحدَّث: إشعار مباشر لمكتب الرئاسة (موافق واحد)
            $this->notifyUniversityOfficeOfNewRequest($event);

            return [
                'success' => true,
                'message' => 'تم إرسال الفعالية لمكتب الرئاسة',
            ];
        });
    }

    /**
     * إعادة إرسال فعالية مرفوضة (alias لـ sendForApproval)
     */
    public function resubmit(Event $event): array
    {
        return $this->sendForApproval($event);
    }

    // ════════════════════════════════════════════════════════════
    // موافقة مكتب الرئاسة
    // ════════════════════════════════════════════════════════════

    /**
     * تسجيل موافقة مكتب الرئاسة على الفعالية
     *
     * - تنشئ سجل approval للدورة الحالية
     * - تنقل الفعالية من ADDED → ACTIVE (تنتظر النشر اليدوي من مدير الإعلام)
     */
    public function approve(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            if ($event->status?->name !== Status::ADDED) {
                return [
                    'success' => false,
                    'message' => 'الفعالية ليست بانتظار الموافقة',
                ];
            }

            // حساب رقم الدورة الحالية (الموافقات الموجودة + 1)
            $thisRound = $this->nextRoundFor($event);

            // إنشاء سجل الموافقة (لا decided_by لأن المكتب جهة واحدة)
            EventApproval::create([
                'event_id'         => $event->id,
                'round_number'     => $thisRound,
                'status'           => EventApproval::STATUS_APPROVED,
                'rejection_reason' => null,
            ]);

            // نقل الفعالية إلى ACTIVE (تنتظر النشر)
            $oldStatusId = $event->status_id;
            $activeStatus = Status::where('name', Status::ACTIVE)->firstOrFail();
            $event->update(['status_id' => $activeStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $activeStatus->id,
            ]);

            // ✨ مُحدَّث: إشعار مباشر لمنشئ الفعالية بالموافقة
            $this->notifyCreatorOfApproval($event);

            return [
                'success' => true,
                'message' => 'تمت الموافقة. الفعالية جاهزة للنشر من قِبل مدير الإعلام',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // رفض من مكتب الرئاسة
    // ════════════════════════════════════════════════════════════

    /**
     * تسجيل رفض مكتب الرئاسة للفعالية
     *
     * - تنشئ سجل approval بحالة rejected
     * - تنقل الفعالية إلى REJECTED (تعود لمدير الإعلام للتعديل)
     */
    public function reject(Event $event, ?string $reason = null): array
    {
        return DB::transaction(function () use ($event, $reason) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            if ($event->status?->name !== Status::ADDED) {
                return [
                    'success' => false,
                    'message' => 'الفعالية ليست بانتظار الموافقة',
                ];
            }

            $thisRound = $this->nextRoundFor($event);

            EventApproval::create([
                'event_id'         => $event->id,
                'round_number'     => $thisRound,
                'status'           => EventApproval::STATUS_REJECTED,
                'rejection_reason' => $reason,  // اختياري (nullable)
            ]);

            // نقل الفعالية إلى REJECTED
            $oldStatusId = $event->status_id;
            $rejectedStatus = Status::where('name', Status::REJECTED)->firstOrFail();
            $event->update(['status_id' => $rejectedStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $rejectedStatus->id,
            ]);

            // ✨ مُحدَّث: إشعار مباشر لمنشئ الفعالية بالرفض
            $this->notifyCreatorOfRejection($event, $reason);

            return [
                'success' => true,
                'message' => 'تم تسجيل الرفض. الفعالية رجعت لمدير الإعلام للتعديل',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // Queries
    // ════════════════════════════════════════════════════════════

    /**
     * جلب كل الفعاليات بانتظار قرار مكتب الرئاسة
     */
    public function getPendingApprovals(): Collection
    {
        return Event::with(['creator', 'status'])
            ->whereHas('status', fn($q) => $q->where('name', Status::ADDED))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * هل وافق مكتب الرئاسة على الفعالية في آخر دورة؟
     *
     * 💡 يحافظ على اسم الـ method القديم للتوافق مع الكود الموجود
     */
    public function areAllApprovalsComplete(Event $event): bool
    {
        return $event->isApprovedByOffice();
    }

    /**
     * هل الفعالية معروضة على مكتب الرئاسة الآن؟
     */
    public function hasPendingApproval(Event $event): bool
    {
        return $event->isPendingApproval();
    }

    // ════════════════════════════════════════════════════════════
    // Internal Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * حساب رقم الدورة التالية للفعالية
     *
     * - أول مرة: 1
     * - بعد رفض → resubmit: 2
     * - بعد رفض ثاني → resubmit: 3
     * - وهكذا...
     */
    protected function nextRoundFor(Event $event): int
    {
        $maxRound = $event->approvals()->max('round_number') ?? 0;
        return $maxRound + 1;
    }

    /**
     * ✨ إشعار كل users بدور university_office بطلب موافقة جديد
     *
     * يُستدعى عند sendForApproval (أول مرة أو إعادة إرسال)
     */
    protected function notifyUniversityOfficeOfNewRequest(Event $event): void
    {
        try {
            $officeRoleId = Role::where('name', Role::UNIVERSITY_OFFICE)->value('id');
            if (!$officeRoleId) {
                \Log::warning('Role university_office not found');
                return;
            }

            $officeUsers = User::where('role_id', $officeRoleId)
                ->where('is_active', true)
                ->get();

            if ($officeUsers->isEmpty()) {
                \Log::warning('No active university_office users to notify');
                return;
            }

            $currentRound = $event->currentRound();
            $isResubmit = $currentRound > 1;

            $title = $isResubmit
                ? '🔄 إعادة إرسال فعالية للموافقة'
                : '📋 طلب موافقة جديد';

            $creatorName = $event->creator->name ?? 'مسؤول الإعلام';

            $message = $isResubmit
                ? "تم إعادة إرسال فعالية \"{$event->title}\" للمراجعة (الدورة #{$currentRound})."
                . "\nقام بإرسالها: {$creatorName}"
                . "\nيرجى مراجعة التعديلات واتخاذ القرار."
                : "تم إرسال فعالية جديدة \"{$event->title}\" للموافقة."
                . "\nقام بإنشائها: {$creatorName}"
                . "\nيرجى المراجعة والاتخاذ القرار المناسب.";

            foreach ($officeUsers as $user) {
                Notification::create([
                    'user_id'  => $user->id,
                    'title'    => $title,
                    'message'  => $message,
                    'type'     => $isResubmit ? 'event_resubmitted' : 'approval_requested',
                    'event_id' => $event->id,
                    'is_read'  => false,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to notify university_office: ' . $e->getMessage());
        }
    }

    /**
     * ✨ إشعار منشئ الفعالية بالموافقة
     */
    protected function notifyCreatorOfApproval(Event $event): void
    {
        try {
            if (!$event->created_by) return;

            Notification::create([
                'user_id'  => $event->created_by,
                'title'    => '✅ تمت الموافقة على فعاليتك',
                'message'  => "تمت الموافقة على فعالية \"{$event->title}\" من قبل مكتب رئاسة الجامعة.\n\n"
                            . "يمكنك الآن نشرها للجمهور من صفحة إدارة الفعاليات.",
                'type'     => 'event_approved',
                'event_id' => $event->id,
                'is_read'  => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to notify creator of approval: ' . $e->getMessage());
        }
    }

    /**
     * ✨ إشعار منشئ الفعالية بالرفض
     */
    protected function notifyCreatorOfRejection(Event $event, ?string $reason): void
    {
        try {
            if (!$event->created_by) return;

            $message = "تم رفض فعالية \"{$event->title}\" من قبل مكتب رئاسة الجامعة.";

            if (!empty($reason)) {
                $message .= "\n\nسبب الرفض:\n{$reason}";
            }

            $message .= "\n\nيمكنك تعديلها وإعادة إرسالها في دورة جديدة.";

            Notification::create([
                'user_id'  => $event->created_by,
                'title'    => '⛔ تم رفض فعاليتك',
                'message'  => $message,
                'type'     => 'event_rejected',
                'event_id' => $event->id,
                'is_read'  => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to notify creator of rejection: ' . $e->getMessage());
        }
    }

    /**
     * 🔒 محتفظ به للتوافق - لكنه فارغ حالياً
     * (نستخدم methods مباشرة بدلاً من الـ NotificationService الخارجي)
     */
    protected function safeNotify(string $method, ...$args): void
    {
        // deprecated - kept for backward compatibility only
    }
}
