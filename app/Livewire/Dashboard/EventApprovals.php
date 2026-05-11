<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\EventApproval;
use App\Models\Role;
use App\Services\EventApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * EventApprovals — UOMTheatre
 * ════════════════════════════════════════════════════════════════
 *
 * شاشة "الفعاليات بانتظار موافقتي"
 * تُستخدم من قبل:
 *   - مدير المسرح (theater_manager)
 *   - مدير مكتب الرئيس (university_office)
 *
 * كل دور يرى فقط الفعاليات التي بانتظار موافقته.
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('الفعاليات بانتظار موافقتي')]
class EventApprovals extends BaseComponent
{
    // ════════════════════════════════════════════════════════════
    // حقول الرفض
    // ════════════════════════════════════════════════════════════
    public ?int $rejectingApprovalId = null;
    public string $rejectionNote = '';
    public string $rejectingEventTitle = '';

    // ════════════════════════════════════════════════════════════
    // فتح نافذة الرفض
    // ════════════════════════════════════════════════════════════
    public function openRejectModal(int $approvalId)
    {
        $approval = EventApproval::with('event')->findOrFail($approvalId);

        // التأكد أن الـ approval تخص دور المستخدم
        if ($approval->role_id !== Auth::user()->role_id) {
            $this->swalError('غير مصرح لك');
            return;
        }

        $this->rejectingApprovalId = $approvalId;
        $this->rejectionNote = '';
        $this->rejectingEventTitle = $approval->event->title;

        $this->dispatch('open-modal', id: 'rejectApprovalModal');
    }

    // ════════════════════════════════════════════════════════════
    // إلغاء عملية الرفض
    // ════════════════════════════════════════════════════════════
    public function cancelReject()
    {
        $this->reset(['rejectingApprovalId', 'rejectionNote', 'rejectingEventTitle']);
    }

    // ════════════════════════════════════════════════════════════
    // طلب تأكيد الموافقة
    // ════════════════════════════════════════════════════════════
    public function requestApprove(int $approvalId)
    {
        $approval = EventApproval::with('event')->findOrFail($approvalId);

        if ($approval->role_id !== Auth::user()->role_id) {
            $this->swalError('غير مصرح لك');
            return;
        }

        $this->swalConfirm(
            message: "هل أنت متأكد من الموافقة على فعالية \"{$approval->event->title}\"؟",
            action: 'confirmApprove',
            params: $approvalId,
            title: 'تأكيد الموافقة'
        );
    }

    // ════════════════════════════════════════════════════════════
    // تنفيذ الموافقة بعد التأكيد
    // ════════════════════════════════════════════════════════════
    #[On('confirmApprove')]
    public function confirmApprove($id = null)
    {
        $approvalId = is_array($id) ? ($id['id'] ?? $id) : $id;

        if (!$approvalId) {
            $this->swalError('معرّف الموافقة غير صحيح');
            return;
        }

        try {
            $approval = EventApproval::with('event')->findOrFail($approvalId);

            // تحقق إضافي للأمان
            if ($approval->role_id !== Auth::user()->role_id) {
                $this->swalError('غير مصرح لك');
                return;
            }

            $service = app(EventApprovalService::class);
            $result = $service->approve($approval->event, Auth::user());

            if ($result['success']) {
                if ($result['all_approved']) {
                    $this->swalSuccess($result['message']);
                } else {
                    $this->swalToast($result['message']);
                }
            } else {
                $this->swalError($result['message']);
            }
        } catch (\Exception $e) {
            $this->swalError('فشل تسجيل الموافقة: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // تنفيذ الرفض (من نموذج الرفض)
    // ════════════════════════════════════════════════════════════
    public function submitReject()
    {
        $this->validate([
            'rejectionNote' => 'required|string|max:500',
        ], [
            'rejectionNote.required' => 'يرجى كتابة سبب الرفض',
            'rejectionNote.max'      => 'الحد الأقصى 500 حرف',
        ]);

        try {
            $approval = EventApproval::with('event')->findOrFail($this->rejectingApprovalId);

            if ($approval->role_id !== Auth::user()->role_id) {
                $this->swalError('غير مصرح لك');
                return;
            }

            $service = app(EventApprovalService::class);
            $result = $service->reject($approval->event, Auth::user(), $this->rejectionNote);

            if ($result['success']) {
                $this->swalSuccess($result['message']);
                $this->reset(['rejectingApprovalId', 'rejectionNote', 'rejectingEventTitle']);
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
        $roleName = $user->role->name;

        // التأكد أن الدور يستطيع الموافقة
        $allowedRoles = [Role::THEATER_MANAGER, Role::UNIVERSITY_OFFICE, Role::SUPER_ADMIN];
        if (!in_array($roleName, $allowedRoles)) {
            return redirect()->route('dashboard');
        }

        // للسوبر أدمن: نعرض كل الموافقات المعلّقة (للمراجعة)
        // لباقي الأدوار: فقط ما يخص دورهم
        if ($roleName === Role::SUPER_ADMIN) {
            $approvals = EventApproval::with(['event.status', 'event.creator', 'role'])
                ->where('status', EventApproval::STATUS_PENDING)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $approvals = EventApproval::with(['event.status', 'event.creator', 'role'])
                ->where('role_id', $user->role_id)
                ->where('status', EventApproval::STATUS_PENDING)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // إحصائيات سريعة
        $stats = [
            'pending_count'   => $approvals->count(),
            'role_label'      => $roleName === Role::THEATER_MANAGER ? 'مدير المسرح' :
                                ($roleName === Role::UNIVERSITY_OFFICE ? 'مكتب رئيس الجامعة' : 'كل الموافقات'),
        ];

        return view('livewire.dashboard.event-approvals', [
            'approvals' => $approvals,
            'stats'     => $stats,
            'roleName'  => $roleName,
        ]);
    }
}
