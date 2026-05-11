<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\EventLog;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 * EventApprovalService — UOMTheatre (مُحدّث)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات في هذه النسخة (إصلاحات Claude):
 *   🔴 إصلاح race condition: lockForUpdate في approve()
 *      كان ممكن "moveEventToActive" تنفّذ مرتين عند موافقة متزامنة
 *   🟡 areAllApprovalsComplete: استخدام whereHas (أبسط) + ===
 *   🟡 sendForApproval: التحقق من الحالة قبل المعالجة
 *   🟡 reject: التحقق من الحالة قبل المعالجة
 *
 * ════════════════════════════════════════════════════════════════
 */
class EventApprovalService
{
    /**
     * الأدوار التي تحتاج موافقتها على كل فعالية
     */
    public const REQUIRED_APPROVER_ROLES = [
        Role::THEATER_MANAGER,
        Role::UNIVERSITY_OFFICE,
    ];

    // ════════════════════════════════════════════════════════════
    // إرسال فعالية للموافقة
    // ════════════════════════════════════════════════════════════

    public function sendForApproval(Event $event): bool
    {
        return DB::transaction(function () use ($event) {
            // ✨ قفل الفعالية لتجنّب race conditions
            $event = Event::lockForUpdate()->findOrFail($event->id);

            // ✨ التحقق من الحالة
            if ($event->status?->name !== Status::DRAFT) {
                return false;
            }

            $oldStatusId = $event->status_id;

            // 1. تحديث حالة الفعالية إلى "added"
            $addedStatus = Status::where('name', Status::ADDED)->firstOrFail();
            $event->update(['status_id' => $addedStatus->id]);

            // 2. تسجيل تغيير الحالة في event_logs
            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $addedStatus->id,
            ]);

            // 3. إنشاء سجلات pending للأدوار المطلوبة (لو غير موجودة)
            //    ✨ هذا هو سرّ الحفاظ على الموافقات السابقة!
            foreach (self::REQUIRED_APPROVER_ROLES as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if (!$role) {
                    continue;
                }

                $existingApproval = EventApproval::where('event_id', $event->id)
                    ->where('role_id', $role->id)
                    ->first();

                if (!$existingApproval) {
                    // أول مرة: ننشئ سجل جديد بحالة pending
                    EventApproval::create([
                        'event_id' => $event->id,
                        'user_id'  => Auth::id(),
                        'role_id'  => $role->id,
                        'status'   => EventApproval::STATUS_PENDING,
                    ]);
                } elseif ($existingApproval->isRejected()) {
                    // ✨ كان رفض من قبل: نعيد فقط الرافض إلى pending
                    //    الموافق الآخر يبقى approved (لا نلمسه)
                    $existingApproval->update([
                        'status'      => EventApproval::STATUS_PENDING,
                        'note'        => null,
                        'rejected_at' => null,
                    ]);
                }
                // لو كان approved من قبل: نتركه كما هو (محفوظ ✓)
            }

            // 4. إرسال إشعار (للأدوار pending فقط بعد إصلاح NotificationService)
            app(NotificationService::class)->notifyApprovalRequested($event);

            return true;
        });
    }

    // ════════════════════════════════════════════════════════════
    // موافقة دور معيّن
    // ════════════════════════════════════════════════════════════

    /**
     * 🔴 مُصحَّح: race condition عبر lockForUpdate
     */
    public function approve(Event $event, User $user): array
    {
        return DB::transaction(function () use ($event, $user) {
            // ✨ قفل الفعالية لتجنّب race condition
            $event = Event::lockForUpdate()->findOrFail($event->id);

            // البحث عن سجل الموافقة المعلّق لهذا الدور
            $approval = EventApproval::where('event_id', $event->id)
                ->where('role_id', $user->role_id)
                ->where('status', EventApproval::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (!$approval) {
                return [
                    'success'      => false,
                    'all_approved' => false,
                    'message'      => 'لا يوجد طلب موافقة معلّق لدورك على هذه الفعالية',
                ];
            }

            // تسجيل الموافقة
            $approval->update([
                'user_id'     => $user->id,
                'status'      => EventApproval::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            // التحقق: هل وافق الجميع؟
            $allApproved = $this->areAllApprovalsComplete($event);

            // ✨ حماية إضافية: لا ننقل إلى active إلا لو الفعالية ما زالت added
            //    (لو كان moveEventToActive نُفّذ مسبقاً، الحالة تكون active)
            if ($allApproved && $event->status?->name === Status::ADDED) {
                $this->moveEventToActive($event);
            }

            return [
                'success'      => true,
                'all_approved' => $allApproved,
                'message'      => $allApproved
                    ? 'تمت الموافقة! الفعالية أصبحت جاهزة للنشر'
                    : 'تمت موافقتك. بانتظار موافقة الجهة الأخرى',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // رفض دور معيّن
    // ════════════════════════════════════════════════════════════

    public function reject(Event $event, User $user, ?string $note = null): array
    {
        return DB::transaction(function () use ($event, $user, $note) {
            // ✨ قفل الفعالية + التحقق من الحالة
            $event = Event::lockForUpdate()->findOrFail($event->id);

            if ($event->status?->name !== Status::ADDED) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن رفض الفعالية في حالتها الحالية',
                ];
            }

            $approval = EventApproval::where('event_id', $event->id)
                ->where('role_id', $user->role_id)
                ->where('status', EventApproval::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (!$approval) {
                return [
                    'success' => false,
                    'message' => 'لا يوجد طلب موافقة معلّق لدورك على هذه الفعالية',
                ];
            }

            // تسجيل الرفض
            $approval->update([
                'user_id'     => $user->id,
                'status'      => EventApproval::STATUS_REJECTED,
                'note'        => $note,
                'rejected_at' => now(),
            ]);

            // إعادة الفعالية إلى draft
            $oldStatusId = $event->status_id;
            $draftStatus = Status::where('name', Status::DRAFT)->firstOrFail();

            $event->update(['status_id' => $draftStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => $user->id,
                'old_status_id' => $oldStatusId,
                'new_status_id' => $draftStatus->id,
            ]);

            // إرسال إشعار رفض لمدير الإعلام
            app(NotificationService::class)->notifyEventRejected($event, $user, $note);

            return [
                'success' => true,
                'message' => 'تم تسجيل رفضك وإعادة الفعالية لمدير الإعلام للتعديل',
            ];
        });
    }

    // ════════════════════════════════════════════════════════════
    // Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * هل أكمل كل الموافقين موافقتهم؟
     *
     * ✨ مُحسّن: استخدام whereHas (أبسط) + استخدام === بدل >=
     */
    public function areAllApprovalsComplete(Event $event): bool
    {
        $requiredCount = count(self::REQUIRED_APPROVER_ROLES);

        $approvedCount = EventApproval::where('event_id', $event->id)
            ->where('status', EventApproval::STATUS_APPROVED)
            ->whereHas('role', fn($q) => $q->whereIn('name', self::REQUIRED_APPROVER_ROLES))
            ->count();

        return $approvedCount === $requiredCount;
    }

    /**
     * هل وافق دور معيّن على الفعالية؟
     */
    public function hasRoleApproved(Event $event, string $roleName): bool
    {
        return EventApproval::where('event_id', $event->id)
            ->whereHas('role', fn($q) => $q->where('name', $roleName))
            ->where('status', EventApproval::STATUS_APPROVED)
            ->exists();
    }

    /**
     * هل المستخدم الحالي عنده طلب موافقة معلّق على الفعالية؟
     */
    public function hasPendingApprovalFor(Event $event, User $user): bool
    {
        return EventApproval::where('event_id', $event->id)
            ->where('role_id', $user->role_id)
            ->where('status', EventApproval::STATUS_PENDING)
            ->exists();
    }

    /**
     * جلب كل الموافقات المعلّقة لدور معيّن
     */
    public function getPendingApprovalsForRole(string $roleName)
    {
        return EventApproval::with(['event.status', 'event.creator'])
            ->whereHas('role', fn($q) => $q->where('name', $roleName))
            ->where('status', EventApproval::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    // ════════════════════════════════════════════════════════════
    // Internal Methods
    // ════════════════════════════════════════════════════════════

    /**
     * نقل الفعالية إلى حالة active بعد اكتمال كل الموافقات
     */
    protected function moveEventToActive(Event $event): void
    {
        $oldStatusId  = $event->status_id;
        $activeStatus = Status::where('name', Status::ACTIVE)->firstOrFail();

        $event->update(['status_id' => $activeStatus->id]);

        EventLog::create([
            'event_id'      => $event->id,
            'user_id'       => Auth::id(),
            'old_status_id' => $oldStatusId,
            'new_status_id' => $activeStatus->id,
        ]);

        // إرسال إشعار لمدير الإعلام: تمت كل الموافقات
        app(NotificationService::class)->notifyApprovalsComplete($event);
    }
}
