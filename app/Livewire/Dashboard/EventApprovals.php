<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Role;
use App\Models\Status;
use App\Services\EventApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * EventApprovals — UOMTheatre (تصميم جديد + زر تفاصيل)
 * ════════════════════════════════════════════════════════════════
 *
 * شاشة "الفعاليات بانتظار موافقتي"
 *
 * 🎯 الميزات:
 *   - عرض الفعاليات بحالة "added"
 *   - 3 أزرار: تفاصيل 👁️ | موافقة ✅ | رفض ❌
 *   - modal تفاصيل: يعرض كل المعلومات + سجل الرفض السابق
 *   - rejection_reason اختياري
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('الفعاليات بانتظار موافقتي')]
class EventApprovals extends BaseComponent
{
    // ════════════════════════════════════════════════════════════
    // حقول الرفض (modal)
    // ════════════════════════════════════════════════════════════
    public ?int $rejectingEventId = null;
    public string $rejectionNote = '';
    public string $rejectingEventTitle = '';

    // ════════════════════════════════════════════════════════════
    // ✨ جديد: حقل عرض التفاصيل (modal)
    // ════════════════════════════════════════════════════════════
    public ?int $viewingEventId = null;

    // ════════════════════════════════════════════════════════════
    // ✨ جديد: فتح نافذة التفاصيل
    // ════════════════════════════════════════════════════════════
    public function openDetailsModal(int $eventId)
    {
        $this->viewingEventId = $eventId;
        $this->dispatch('open-modal', id: 'eventDetailsModal');
    }

    public function closeDetailsModal()
    {
        $this->viewingEventId = null;
    }

    // ════════════════════════════════════════════════════════════
    // فتح نافذة الرفض
    // ════════════════════════════════════════════════════════════
    public function openRejectModal(int $eventId)
    {
        $event = Event::with('status')->findOrFail($eventId);

        if ($event->status?->name !== Status::ADDED) {
            $this->swalError('هذه الفعالية ليست بانتظار قرار');
            return;
        }

        $this->rejectingEventId    = $eventId;
        $this->rejectionNote       = '';
        $this->rejectingEventTitle = $event->title;

        $this->dispatch('open-modal', id: 'rejectApprovalModal');
    }

    public function cancelReject()
    {
        $this->reset(['rejectingEventId', 'rejectionNote', 'rejectingEventTitle']);
    }

    // ════════════════════════════════════════════════════════════
    // طلب تأكيد الموافقة
    // ════════════════════════════════════════════════════════════
    public function requestApprove(int $eventId)
    {
        $event = Event::with('status')->findOrFail($eventId);

        if ($event->status?->name !== Status::ADDED) {
            $this->swalError('هذه الفعالية ليست بانتظار قرار');
            return;
        }

        $this->swalConfirm(
            message: "هل أنتِ متأكدة من الموافقة على فعالية \"{$event->title}\"؟",
            action: 'confirmApprove',
            params: $eventId,
            title: 'تأكيد الموافقة'
        );
    }

    #[On('confirmApprove')]
    public function confirmApprove($id = null)
    {
        $eventId = is_array($id) ? ($id['id'] ?? $id) : $id;

        if (!$eventId) {
            $this->swalError('معرّف الفعالية غير صحيح');
            return;
        }

        try {
            $event = Event::findOrFail($eventId);

            $service = app(EventApprovalService::class);
            $result = $service->approve($event);

            if ($result['success']) {
                $this->swalSuccess($result['message']);
            } else {
                $this->swalError($result['message']);
            }
        } catch (\Exception $e) {
            $this->swalError('فشل تسجيل الموافقة: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // تنفيذ الرفض
    // ════════════════════════════════════════════════════════════
    public function submitReject()
    {
        $this->validate([
            'rejectionNote' => 'nullable|string|max:500',
        ], [
            'rejectionNote.max' => 'الحد الأقصى 500 حرف',
        ]);

        try {
            $event = Event::findOrFail($this->rejectingEventId);

            $service = app(EventApprovalService::class);
            $reason = trim($this->rejectionNote) ?: null;
            $result = $service->reject($event, $reason);

            if ($result['success']) {
                $this->swalSuccess($result['message']);
                $this->reset(['rejectingEventId', 'rejectionNote', 'rejectingEventTitle']);
                $this->dispatch('close-modal');
            } else {
                $this->swalError($result['message']);
            }
        } catch (\Exception $e) {
            $this->swalError('فشل تسجيل الرفض: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // Render
    // ════════════════════════════════════════════════════════════
    public function render()
    {
        $user = Auth::user();
        $roleName = $user->role?->name;

        $allowedRoles = [Role::SUPER_ADMIN, Role::UNIVERSITY_OFFICE];
        if (!in_array($roleName, $allowedRoles, true)) {
            return redirect()->route('dashboard');
        }

        $service = app(EventApprovalService::class);
        $events = $service->getPendingApprovals();

        $stats = [
            'pending_count' => $events->count(),
            'role_label'    => $roleName === Role::SUPER_ADMIN
                ? 'مدير النظام (مراقبة)'
                : 'مدير مكتب رئاسة الجامعة',
        ];

        // ✨ جديد: تحميل تفاصيل الفعالية المعروضة (لو فيه)
        $viewingEvent = null;
        if ($this->viewingEventId) {
            $viewingEvent = Event::with(['creator', 'status', 'approvals'])
                ->find($this->viewingEventId);
        }

        return view('livewire.dashboard.event-approvals', [
            'events'       => $events,
            'stats'        => $stats,
            'roleName'     => $roleName,
            'viewingEvent' => $viewingEvent,
        ]);
    }
}
