<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\EventLog;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventApprovalService
{
    public function sendForApproval(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);

            $allowedFromStatuses = [Status::DRAFT, Status::REJECTED];
            if (!in_array($event->status?->name, $allowedFromStatuses, true)) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إرسال الفعالية في حالتها الحالية',
                ];
            }

            $oldStatusId = $event->status_id;
            $addedStatus = Status::where('name', Status::ADDED)->firstOrFail();
            $event->update(['status_id' => $addedStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $addedStatus->id,
            ]);

            $this->notifyUniversityOfficeOfNewRequest($event);

            return [
                'success' => true,
                'message' => 'تم إرسال الفعالية لمكتب الرئاسة',
            ];
        });
    }

    public function resubmit(Event $event): array
    {
        return $this->sendForApproval($event);
    }

    protected function authorizeApprover(?User $actor): User
    {
        if (!$actor || !($actor->isSuperAdmin() || $actor->hasPermission(Permission::EVENTS_APPROVE_OFFICE))) {
            throw new AuthorizationException('غير مصرح لك بالبت في طلبات الموافقة');
        }
        return $actor;
    }

    public function approve(Event $event, ?User $actor = null): array
    {
        $actor = $this->authorizeApprover($actor ?? Auth::user());
        return DB::transaction(function () use ($event, $actor) {
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
                'status'           => EventApproval::STATUS_APPROVED,
                'rejection_reason' => null,
                'user_id'          => $actor->id,
                'role_id'          => $actor->role_id,
            ]);

            $oldStatusId = $event->status_id;
            $activeStatus = Status::where('name', Status::ACTIVE)->firstOrFail();
            $event->update(['status_id' => $activeStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $activeStatus->id,
            ]);

            $this->notifyCreatorOfApproval($event);

            return [
                'success' => true,
                'message' => 'تمت الموافقة. الفعالية جاهزة للنشر من قِبل مدير الإعلام',
            ];
        });
    }

    public function reject(Event $event, ?string $reason = null, ?User $actor = null): array
    {
        $actor = $this->authorizeApprover($actor ?? Auth::user());
        return DB::transaction(function () use ($event, $reason, $actor) {
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
                'rejection_reason' => $reason,
                'user_id'          => $actor->id,
                'role_id'          => $actor->role_id,
            ]);

            $oldStatusId = $event->status_id;
            $rejectedStatus = Status::where('name', Status::REJECTED)->firstOrFail();
            $event->update(['status_id' => $rejectedStatus->id]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $rejectedStatus->id,
            ]);

            $this->notifyCreatorOfRejection($event, $reason);

            return [
                'success' => true,
                'message' => 'تم تسجيل الرفض. الفعالية رجعت لمدير الإعلام للتعديل',
            ];
        });
    }

    public function getPendingApprovals(): Collection
    {
        return Event::with(['creator', 'status'])
            ->whereHas('status', fn($q) => $q->where('name', Status::ADDED))
            ->orderByDesc('created_at')
            ->get();
    }

    public function areAllApprovalsComplete(Event $event): bool
    {
        return $event->isApprovedByOffice();
    }

    public function hasPendingApproval(Event $event): bool
    {
        return $event->isPendingApproval();
    }

    protected function nextRoundFor(Event $event): int
    {
        $maxRound = $event->approvals()->max('round_number') ?? 0;
        return $maxRound + 1;
    }

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

    protected function safeNotify(string $method, ...$args): void
    {
    }
}
