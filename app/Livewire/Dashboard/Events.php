<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
#[Title('الفعاليات')]
class Events extends BaseComponent
{
    // ==================== Create Properties ====================
    public string $title = '';
    public string $description = '';
    public string $start_date = '';
    public string $start_time = '';
    public string $end_date = '';
    public string $end_time = '';

    // ==================== Edit Properties ====================
    public int $editId = 0;
    public string $editTitle = '';
    public string $editDescription = '';
    public string $editStartDate = '';
    public string $editStartTime = '';
    public string $editEndDate = '';
    public string $editEndTime = '';

    // ==================== Cancel Properties ====================
    public int $cancelEventId = 0;
    public string $cancelReason = '';
    public bool $isCancelingPublished = false;
    public string $cancelEventTitle = '';
    public int $cancelReservationsCount = 0;

    public array $showEvent = [];

    // ==================== Helper: دمج التاريخ والوقت ====================
    private function combineDateTime(string $date, string $time): string
    {
        if (strlen($time) === 5) {
            $time .= ':00';
        }
        return $date . ' ' . $time;
    }

    // ==================== ✨ 🆕 إنهاء تلقائي للفعاليات المنتهية ====================
    /**
     * يحوّل الفعاليات النشطة/المنشورة التي تجاوز end_datetime الوقت الحالي
     * إلى حالة "end" تلقائياً.
     *
     * يُستدعى في كل مرة تُفتح فيها صفحة الفعاليات (في render).
     * خفيف جداً: استعلام واحد UPDATE مع WHERE conditions.
     *
     * المنطق:
     * - فقط الحالات (active, published) تتحوّل
     *   (لأن draft/under_review قد يكون لها تاريخ قديم لم يُجدول بعد)
     * - الفعاليات الملغاة/المغلقة لا تُمس
     * - is_booking_paused تُمسح عند الانتهاء (لا معنى للإيقاف بعد الانتهاء)
     */
    private function autoEndExpiredEvents(): void
    {
        try {
            $endStatus = Status::where('name', 'end')->first();
            if (!$endStatus) {
                return; // إذا لم تكن حالة 'end' موجودة، نتجاهل بصمت
            }

            // الفعاليات التي ستتحوّل (لتسجيلها في EventLog)
            $expiredEvents = Event::where('end_datetime', '<', now())
                ->whereHas('status', fn($q) => $q->whereIn('name', ['active', 'published']))
                ->get(['id', 'status_id']);

            if ($expiredEvents->isEmpty()) {
                return;
            }

            // التحديث الجماعي
            $expiredIds = $expiredEvents->pluck('id')->toArray();
            Event::whereIn('id', $expiredIds)->update([
                'status_id'         => $endStatus->id,
                'is_booking_paused' => false,
                'paused_at'         => null,
            ]);

            // تسجيل في EventLog لكل فعالية (للتدقيق)
            foreach ($expiredEvents as $event) {
                EventLog::create([
                    'event_id'      => $event->id,
                    'user_id'       => Auth::id(),
                    'old_status_id' => $event->status_id,
                    'new_status_id' => $endStatus->id,
                ]);
            }
        } catch (\Exception $e) {
            // فشل صامت: لا نريد كسر الصفحة بسبب خطأ في الفحص التلقائي
            \Log::error('autoEndExpiredEvents failed: ' . $e->getMessage());
        }
    }

    // ==================== ✨ 🆕 التحقق من منطقية التاريخ ====================
    /**
     * يتحقق أن وقت الانتهاء لم يمر بعد.
     * يرجع رسالة خطأ إن كان غير منطقي، أو null إن كان سليماً.
     */
    private function validateDatetimeLogic(string $startDatetime, string $endDatetime): ?string
    {
        $now = now();

        // الانتهاء يجب أن يكون بعد البدء
        if (strtotime($endDatetime) <= strtotime($startDatetime)) {
            return 'وقت الانتهاء يجب أن يكون بعد وقت البدء';
        }

        // ✨ الانتهاء يجب أن يكون في المستقبل (المنطق الجديد)
        if (strtotime($endDatetime) <= $now->timestamp) {
            return 'لا يمكن إنشاء/تعديل فعالية انتهت بالفعل. يجب أن يكون وقت الانتهاء في المستقبل.';
        }

        return null;
    }

    // ==================== إنشاء فعالية ====================
    public function createEvent()
    {
        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'end_time'    => 'required|date_format:H:i',
        ], [
            'title.required'             => 'عنوان الفعالية مطلوب',
            'start_date.required'        => 'تاريخ البدء مطلوب',
            'start_date.after_or_equal'  => 'تاريخ البدء يجب أن يكون اليوم أو في المستقبل',
            'start_time.required'        => 'وقت البدء مطلوب',
            'start_time.date_format'     => 'صيغة وقت البدء غير صحيحة',
            'end_date.required'          => 'تاريخ الانتهاء مطلوب',
            'end_date.after_or_equal'    => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'end_time.required'          => 'وقت الانتهاء مطلوب',
            'end_time.date_format'       => 'صيغة وقت الانتهاء غير صحيحة',
        ]);

        $startDatetime = $this->combineDateTime($this->start_date, $this->start_time);
        $endDatetime   = $this->combineDateTime($this->end_date, $this->end_time);

        // ✨ التحقق من منطقية التاريخ (جديد)
        $logicError = $this->validateDatetimeLogic($startDatetime, $endDatetime);
        if ($logicError) {
            $this->addError('end_time', $logicError);
            return;
        }

        try {
            $draftStatus = Status::where('name', 'draft')->first();
            $event = Event::create([
                'title'          => $this->title,
                'description'    => $this->description,
                'start_datetime' => $startDatetime,
                'end_datetime'   => $endDatetime,
                'status_id'      => $draftStatus->id,
                'created_by'     => Auth::id(),
            ]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => null,
                'new_status_id' => $draftStatus->id,
            ]);

            $this->swalSuccess('تم إنشاء الفعالية "' . $this->title . '" بنجاح');
            $this->reset(['title', 'description', 'start_date', 'start_time', 'end_date', 'end_time']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل إنشاء الفعالية: ' . $e->getMessage());
        }
    }

    // ==================== عرض التفاصيل ====================
    public function viewEvent(int $id)
    {
        $event = Event::with(['status', 'creator'])->findOrFail($id);

        $duration = $event->durationInMinutes();
        $hours    = floor($duration / 60);
        $minutes  = $duration % 60;
        $durationText = '';
        if ($hours > 0)   $durationText .= "{$hours} ساعة ";
        if ($minutes > 0) $durationText .= "{$minutes} دقيقة";
        if (empty($durationText)) $durationText = 'غير محدد';

        $this->showEvent = [
            'title'               => $event->title,
            'description'         => $event->description ?? 'لا يوجد وصف',
            'start_datetime'      => $event->start_datetime->format('Y-m-d H:i'),
            'end_datetime'        => $event->end_datetime->format('Y-m-d H:i'),
            'duration'            => $durationText,
            'status'              => $event->status->display_name,
            'status_name'         => $event->status->name,
            'created_by'          => $event->creator->name,
            'created_at'          => $event->created_at->format('Y-m-d H:i'),
            'published_at'        => $event->published_at ? $event->published_at->format('Y-m-d H:i') : 'لم تنشر بعد',
            'cancellation_reason' => $event->cancellation_reason,
            'cancelled_at'        => $event->cancelled_at ? $event->cancelled_at->format('Y-m-d H:i') : null,
            'is_booking_paused'   => $event->is_booking_paused,
            'paused_at'           => $event->paused_at ? $event->paused_at->format('Y-m-d H:i') : null,
        ];
    }

    // ==================== فتح نافذة التعديل ====================
    public function openEdit(int $id)
    {
        $event = Event::findOrFail($id);
        $this->editId          = $event->id;
        $this->editTitle       = $event->title;
        $this->editDescription = $event->description ?? '';

        $this->editStartDate = $event->start_datetime->format('Y-m-d');
        $this->editStartTime = $event->start_datetime->format('H:i');
        $this->editEndDate   = $event->end_datetime->format('Y-m-d');
        $this->editEndTime   = $event->end_datetime->format('H:i');
    }

    // ==================== تحديث فعالية ====================
    public function updateEvent()
    {
        $this->validate([
            'editTitle'       => 'required|string|max:255',
            'editStartDate'   => 'required|date',
            'editStartTime'   => 'required|date_format:H:i',
            'editEndDate'     => 'required|date|after_or_equal:editStartDate',
            'editEndTime'     => 'required|date_format:H:i',
        ], [
            'editTitle.required'            => 'العنوان مطلوب',
            'editStartDate.required'        => 'تاريخ البدء مطلوب',
            'editStartTime.required'        => 'وقت البدء مطلوب',
            'editStartTime.date_format'     => 'صيغة وقت البدء غير صحيحة',
            'editEndDate.required'          => 'تاريخ الانتهاء مطلوب',
            'editEndDate.after_or_equal'    => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'editEndTime.required'          => 'وقت الانتهاء مطلوب',
            'editEndTime.date_format'       => 'صيغة وقت الانتهاء غير صحيحة',
        ]);

        $startDatetime = $this->combineDateTime($this->editStartDate, $this->editStartTime);
        $endDatetime   = $this->combineDateTime($this->editEndDate, $this->editEndTime);

        // ✨ التحقق من منطقية التاريخ (جديد)
        $logicError = $this->validateDatetimeLogic($startDatetime, $endDatetime);
        if ($logicError) {
            $this->addError('editEndTime', $logicError);
            return;
        }

        try {
            Event::findOrFail($this->editId)->update([
                'title'          => $this->editTitle,
                'description'    => $this->editDescription,
                'start_datetime' => $startDatetime,
                'end_datetime'   => $endDatetime,
            ]);

            $this->swalSuccess('تم تعديل الفعالية بنجاح');
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل التعديل: ' . $e->getMessage());
        }
    }

    // ==================== فتح نافذة الإلغاء ====================
    public function openCancelModal(int $eventId)
    {
        $event = Event::with('status')->findOrFail($eventId);

        $this->cancelEventId    = $event->id;
        $this->cancelEventTitle = $event->title;
        $this->cancelReason     = '';

        $this->isCancelingPublished = ($event->status->name === 'published');

        $this->cancelReservationsCount = $event->reservations()
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    // ==================== تنفيذ الإلغاء مع السبب ====================
    public function confirmCancelEvent()
    {
        $this->validate([
            'cancelReason' => 'nullable|string|max:500',
        ], [
            'cancelReason.max' => 'سبب الإلغاء يجب ألا يتجاوز 500 حرف',
        ]);

        try {
            $event       = Event::findOrFail($this->cancelEventId);
            $oldStatusId = $event->status_id;
            $cancelledStatus = Status::where('name', 'cancelled')->first();

            if (!$cancelledStatus) {
                $this->swalError('حالة الإلغاء غير موجودة');
                return;
            }

            $event->update([
                'status_id'           => $cancelledStatus->id,
                'cancellation_reason' => !empty($this->cancelReason) ? $this->cancelReason : null,
                'cancelled_at'        => now(),
            ]);

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $cancelledStatus->id,
            ]);

            $this->swalSuccess('تم إلغاء الفعالية "' . $event->title . '" بنجاح');

            $this->reset(['cancelEventId', 'cancelReason', 'cancelEventTitle', 'cancelReservationsCount', 'isCancelingPublished']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل الإلغاء: ' . $e->getMessage());
        }
    }

    // ==================== طلب تأكيد إيقاف الحجز ====================
    public function requestPauseBooking(int $eventId)
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            $this->swalError('ليس لديك صلاحية لإيقاف الحجز');
            return;
        }

        $event = Event::findOrFail($eventId);

        $this->swalConfirm(
            message: "سيتم إيقاف الحجوزات الجديدة للفعالية \"{$event->title}\" مؤقتاً.\nالحجوزات السابقة ستبقى محفوظة.\nيمكن استئناف الحجز في أي وقت.",
            action: 'confirmPauseBooking',
            params: $eventId,
            title: 'تأكيد إيقاف الحجز'
        );
    }

    // ==================== تنفيذ إيقاف الحجز ====================
    #[On('confirmPauseBooking')]
    public function confirmPauseBooking($id = null)
    {
        $eventId = is_array($id) ? ($id['id'] ?? null) : (is_object($id) ? ($id->id ?? null) : $id);

        if (!$eventId) {
            $this->swalError('معرّف الفعالية غير صحيح');
            return;
        }

        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            $this->swalError('ليس لديك صلاحية لإيقاف الحجز');
            return;
        }

        try {
            $event = Event::findOrFail($eventId);

            $event->update([
                'is_booking_paused' => true,
                'paused_at'         => now(),
            ]);

            $this->swalToast('تم إيقاف الحجز للفعالية "' . $event->title . '"');
        } catch (\Exception $e) {
            $this->swalError('فشل إيقاف الحجز: ' . $e->getMessage());
        }
    }

    // ==================== طلب تأكيد استئناف الحجز ====================
    public function requestResumeBooking(int $eventId)
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            $this->swalError('ليس لديك صلاحية لاستئناف الحجز');
            return;
        }

        $event = Event::findOrFail($eventId);

        $this->swalConfirm(
            message: "سيتم استئناف الحجوزات الجديدة للفعالية \"{$event->title}\".\nسيتمكن المستخدمون من الحجز مرة أخرى.",
            action: 'confirmResumeBooking',
            params: $eventId,
            title: 'تأكيد استئناف الحجز'
        );
    }

    // ==================== تنفيذ استئناف الحجز ====================
    #[On('confirmResumeBooking')]
    public function confirmResumeBooking($id = null)
    {
        $eventId = is_array($id) ? ($id['id'] ?? null) : (is_object($id) ? ($id->id ?? null) : $id);

        if (!$eventId) {
            $this->swalError('معرّف الفعالية غير صحيح');
            return;
        }

        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            $this->swalError('ليس لديك صلاحية لاستئناف الحجز');
            return;
        }

        try {
            $event = Event::findOrFail($eventId);

            $event->update([
                'is_booking_paused' => false,
                'paused_at'         => null,
            ]);

            $this->swalToast('تم استئناف الحجز للفعالية "' . $event->title . '"');
        } catch (\Exception $e) {
            $this->swalError('فشل استئناف الحجز: ' . $e->getMessage());
        }
    }

    // ==================== طلب تأكيد تغيير الحالة ====================
    public function requestChangeStatus(int $eventId, string $newStatusName)
    {
        $messages = [
            'added'        => 'إرسال الفعالية للمراجعة؟',
            'under_review' => 'بدء مراجعة هذه الفعالية؟',
            'active'       => 'قبول الفعالية؟',
            'published'    => 'نشر الفعالية للجمهور؟',
            'closed'       => 'إغلاق الفعالية؟',
        ];

        $titles = [
            'added'        => 'تأكيد الإرسال',
            'under_review' => 'تأكيد المراجعة',
            'active'       => 'تأكيد القبول',
            'published'    => 'تأكيد النشر',
            'closed'       => 'تأكيد الإغلاق',
        ];

        $this->swalConfirm(
            message: $messages[$newStatusName] ?? 'هل أنت متأكد؟',
            action: 'confirmChangeStatus',
            params: ['eventId' => $eventId, 'newStatus' => $newStatusName],
            title: $titles[$newStatusName] ?? 'تأكيد'
        );
    }

    // ==================== تنفيذ تغيير الحالة بعد التأكيد ====================
    #[On('confirmChangeStatus')]
    public function confirmChangeStatus($id = null)
    {
        $eventId       = null;
        $newStatusName = null;

        if (is_array($id)) {
            $eventId       = $id['eventId']   ?? null;
            $newStatusName = $id['newStatus'] ?? null;
        } elseif (is_object($id)) {
            $eventId       = $id->eventId   ?? null;
            $newStatusName = $id->newStatus ?? null;
        }

        if (!$eventId || !$newStatusName) {
            $this->swalError('بيانات التغيير غير مكتملة');
            return;
        }

        try {
            $event       = Event::findOrFail($eventId);
            $oldStatusId = $event->status_id;
            $newStatus   = Status::where('name', $newStatusName)->first();

            if (!$newStatus) {
                $this->swalError('الحالة المطلوبة غير موجودة');
                return;
            }

            // ✨ منع نشر فعالية انتهى وقتها
            if ($newStatusName === 'published' && $event->end_datetime->isPast()) {
                $this->swalError('لا يمكن نشر فعالية انتهى وقتها');
                return;
            }

            $event->status_id = $newStatus->id;
            if ($newStatusName === 'published') $event->published_at = now();
            if ($newStatusName === 'closed')    $event->closed_at    = now();
            $event->save();

            EventLog::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'old_status_id' => $oldStatusId,
                'new_status_id' => $newStatus->id,
            ]);

            $names = [
                'draft'        => 'مسودة',
                'added'        => 'مضافة',
                'under_review' => 'قيد المراجعة',
                'active'       => 'نشطة',
                'published'    => 'منشورة',
                'closed'       => 'مغلقة',
                'cancelled'    => 'ملغاة',
                'end'          => 'منتهية',
            ];

            $this->swalToast('تم تغيير الحالة إلى: ' . ($names[$newStatusName] ?? $newStatusName));
        } catch (\Exception $e) {
            $this->swalError('فشل تغيير الحالة: ' . $e->getMessage());
        }
    }

    // ==================== حذف فعالية ====================
    public function deleteEvent(int $id)
    {
        try {
            $event = Event::findOrFail($id);
            $title = $event->title;
            $event->delete();
            $this->swalSuccess('تم حذف الفعالية "' . $title . '"');
        } catch (\Exception $e) {
            $this->swalError('فشل الحذف: ' . $e->getMessage());
        }
    }

    // ==================== Render ====================
    public function render()
    {
        $roleName = Auth::user()->role->name;
        $allowed  = ['super_admin', 'theater_manager', 'event_manager'];
        if (!in_array($roleName, $allowed)) return redirect()->route('dashboard');

        // ✨ 🆕 الفحص التلقائي قبل عرض القائمة
        $this->autoEndExpiredEvents();

        $events = Event::with(['status', 'creator'])
            ->orderBy('start_datetime', 'desc')
            ->get();

        return view('livewire.dashboard.events', [
            'events'   => $events,
            'roleName' => $roleName,
        ]);
    }
}
