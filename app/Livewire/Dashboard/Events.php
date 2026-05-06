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
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الفعاليات')]
class Events extends BaseComponent
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

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

    // ==================== Search & Filter Properties ====================
    public string $searchTitle = '';
    public string $filterStatus = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public bool $showSuggestions = false;

    /**
     * ✨ الحالات المعروضة في الفلتر (بعد حذف under_review)
     * هذه القائمة مرتبة حسب workflow الفعاليات
     */
    private const VISIBLE_STATUSES = [
        'draft',      // مسودة
        'added',      // مضافة
        'active',     // نشطة
        'published',  // منشورة
        'closed',     // مغلقة
        'cancelled',  // ملغاة
        'end',        // منتهية
    ];

    /**
     * إعادة تعيين كل الفلاتر
     */
    public function resetFilters(): void
    {
        $this->reset(['searchTitle', 'filterStatus', 'filterDateFrom', 'filterDateTo']);
        $this->showSuggestions = false;
        $this->resetPage();

        // ✨ إطلاق حدث لمسح Flatpickr في الـ JavaScript
        $this->dispatch('filters-reset');
    }

    public function selectSuggestion(string $title): void
    {
        $this->searchTitle = $title;
        $this->showSuggestions = false;
        $this->resetPage();
    }

    public function hideSuggestions(): void
    {
        $this->showSuggestions = false;
    }

    public function updatedSearchTitle(): void
    {
        $this->showSuggestions = !empty($this->searchTitle);
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    // ==================== Helper: دمج التاريخ والوقت ====================
    private function combineDateTime(string $date, string $time): string
    {
        if (strlen($time) === 5) {
            $time .= ':00';
        }
        return $date . ' ' . $time;
    }

    // ==================== إنهاء تلقائي للفعاليات المنتهية ====================
    /**
     * يحوّل الفعاليات النشطة/المنشورة التي تجاوز end_datetime الوقت الحالي
     * إلى حالة "end" تلقائياً.
     */
    private function autoEndExpiredEvents(): void
    {
        try {
            $endStatus = Status::where('name', 'end')->first();
            if (!$endStatus) {
                return;
            }

            $expiredEvents = Event::where('end_datetime', '<', now())
                ->whereHas('status', fn($q) => $q->whereIn('name', ['active', 'published']))
                ->get(['id', 'status_id']);

            if ($expiredEvents->isEmpty()) {
                return;
            }

            $expiredIds = $expiredEvents->pluck('id')->toArray();
            Event::whereIn('id', $expiredIds)->update([
                'status_id'         => $endStatus->id,
                'is_booking_paused' => false,
                'paused_at'         => null,
            ]);

            foreach ($expiredEvents as $event) {
                EventLog::create([
                    'event_id'      => $event->id,
                    'user_id'       => Auth::id(),
                    'old_status_id' => $event->status_id,
                    'new_status_id' => $endStatus->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('autoEndExpiredEvents failed: ' . $e->getMessage());
        }
    }

    // ==================== التحقق من منطقية التاريخ ====================
    private function validateDatetimeLogic(string $startDatetime, string $endDatetime): ?string
    {
        $now = now();

        if (strtotime($endDatetime) <= strtotime($startDatetime)) {
            return 'وقت الانتهاء يجب أن يكون بعد وقت البدء';
        }

        if (strtotime($endDatetime) <= $now->timestamp) {
            return 'لا يمكن إنشاء/تعديل فعالية انتهت بالفعل. يجب أن يكون وقت الانتهاء في المستقبل.';
        }

        return null;
    }

    // ==================== إنشاء فعالية ====================
    public function createEvent()
    {
        $this->authorize('create', Event::class);

        $this->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:250',
            'start_date'  => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'end_time'    => 'required|date_format:H:i',
        ], [
            'title.required'             => 'عنوان الفعالية مطلوب',
            'description.max'            => 'يجب ألا يتجاوز الوصف 250 حرف (حوالي 4 أسطر)',
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

        // ✨ فتح الـ modal بعد ما البيانات جاهزة (لا race condition)
        $this->dispatch('open-view-modal');
    }

    // ==================== فتح نافذة التعديل ====================
    public function openEdit(int $id)
    {
        $event = Event::findOrFail($id);
        $this->authorize('update', $event);

        $this->editId          = $event->id;
        $this->editTitle       = $event->title;
        $this->editDescription = $event->description ?? '';

        $this->editStartDate = $event->start_datetime->format('Y-m-d');
        $this->editStartTime = $event->start_datetime->format('H:i');
        $this->editEndDate   = $event->end_datetime->format('Y-m-d');
        $this->editEndTime   = $event->end_datetime->format('H:i');

        // ✨ فتح الـ modal بعد ما البيانات جاهزة (لا race condition)
        $this->dispatch('open-edit-modal');
    }

    // ==================== تحديث فعالية ====================
    public function updateEvent()
    {
        $event = Event::findOrFail($this->editId);
        $this->authorize('update', $event);

        $this->validate([
            'editTitle'       => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:250',
            'editStartDate'   => 'required|date|after_or_equal:today',
            'editStartTime'   => 'required|date_format:H:i',
            'editEndDate'     => 'required|date|after_or_equal:editStartDate',
            'editEndTime'     => 'required|date_format:H:i',
        ], [
            'editTitle.required'            => 'العنوان مطلوب',
            'editDescription.max'           => 'يجب ألا يتجاوز الوصف 250 حرف (حوالي 4 أسطر)',
            'editStartDate.required'        => 'تاريخ البدء مطلوب',
            'editStartDate.after_or_equal'  => 'تاريخ البدء يجب أن يكون اليوم أو في المستقبل',
            'editStartTime.required'        => 'وقت البدء مطلوب',
            'editStartTime.date_format'     => 'صيغة وقت البدء غير صحيحة',
            'editEndDate.required'          => 'تاريخ الانتهاء مطلوب',
            'editEndDate.after_or_equal'    => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'editEndTime.required'          => 'وقت الانتهاء مطلوب',
            'editEndTime.date_format'       => 'صيغة وقت الانتهاء غير صحيحة',
        ]);

        $startDatetime = $this->combineDateTime($this->editStartDate, $this->editStartTime);
        $endDatetime   = $this->combineDateTime($this->editEndDate, $this->editEndTime);

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
        $this->authorize('cancel', $event);

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
            $this->authorize('cancel', $event);

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
        $event = Event::findOrFail($eventId);
        $this->authorize('pauseBooking', $event);

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

        try {
            $event = Event::findOrFail($eventId);
            $this->authorize('pauseBooking', $event);

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
        $event = Event::findOrFail($eventId);
        $this->authorize('resumeBooking', $event);

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

        try {
            $event = Event::findOrFail($eventId);
            $this->authorize('resumeBooking', $event);

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
    /**
     * ✨ مُحدَّث: رسائل تأكيد أكثر وضوحاً وتحذيرية
     */
    public function requestChangeStatus(int $eventId, string $newStatusName)
    {
        $messages = [
            'added'        => 'هل أنت متأكد من معلومات الفعالية؟ لن يمكنك التعديل عليها إذا تم الإرسال إلى مدير الإعلام',
            'active'       => 'قبول الفعالية؟',
            'published'    => 'نشر الفعالية للجمهور؟',
            'closed'       => 'إغلاق الفعالية؟',
        ];

        $titles = [
            'added'        => 'تأكيد الإرسال إلى مدير الإعلام',
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

            // منع نشر فعالية انتهى وقتها
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

            // ✨ مُحدَّث: حذف under_review
            $names = [
                'draft'        => 'مسودة',
                'added'        => 'مضافة',
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
            $this->authorize('delete', $event);

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
        $this->authorize('viewAny', Event::class);

        $roleName = Auth::user()->role->name;

        // الفحص التلقائي قبل عرض القائمة
        $this->autoEndExpiredEvents();

        // ✨ بناء الاستعلام مع الفلاتر
        $query = Event::with(['status', 'creator']);

        // ════════════════════════════════════════════════════════════
        // ✨ 🆕 فلترة بالدور (محدّثة):
        //    - مدير المسرح: يشوف فعالياته فقط (كل الحالات)
        //    - مدير الإعلام: يشوف كل الفعاليات ما عدا المسودات
        // ════════════════════════════════════════════════════════════
        if ($roleName === 'theater_manager') {
            // مدير المسرح يشوف فعالياته فقط (بكل الحالات بما فيها draft)
            $query->where('created_by', Auth::id());
        } elseif ($roleName === 'event_manager') {
            // ✅ مدير الإعلام لا يشوف المسودات (draft خاصة بمدير المسرح فقط)
            $draftStatusId = Status::where('name', 'draft')->value('id');
            if ($draftStatusId) {
                $query->where('status_id', '!=', $draftStatusId);
            }
        }

        // البحث بالعنوان
        if (!empty($this->searchTitle)) {
            $query->where('title', 'like', '%' . $this->searchTitle . '%');
        }

        // الفلتر بالحالة
        if (!empty($this->filterStatus)) {
            $statusObj = Status::where('name', $this->filterStatus)->first();
            if ($statusObj) {
                $query->where('status_id', $statusObj->id);
            }
        }

        // ════════════════════════════════════════════════════════════
        // ✨ 🆕 فلترة بالتاريخ (محسّنة)
        // المنطق: إذا كان نطاق الفعالية يتقاطع مع نطاق الفلترة المختار
        // ════════════════════════════════════════════════════════════

        // الفلتر بتاريخ البدء (من): الفعالية تبدأ في أو بعد هذا التاريخ
        if (!empty($this->filterDateFrom)) {
            $query->whereDate('start_datetime', '>=', $this->filterDateFrom);
        }

        // الفلتر بتاريخ الانتهاء (إلى): الفعالية تبدأ في أو قبل هذا التاريخ
        // (يمكن أن تنتهي بعده، لكن المهم أن تبدأ ضمن النطاق)
        if (!empty($this->filterDateTo)) {
            $query->whereDate('start_datetime', '<=', $this->filterDateTo);
        }

        // ════════════════════════════════════════════════════════════
        // ✨ 🆕 الترتيب الذكي (محدّث):
        //    - مدير الإعلام: المضافة حديثاً (added) أولاً، ثم الأحدث
        //    - باقي الأدوار: الأحدث أولاً (created_at desc)
        // ════════════════════════════════════════════════════════════
        if ($roleName === 'event_manager') {
            // ترتيب أولوية: added أولاً، ثم under_review، ثم البقية
            // باستخدام CASE WHEN لتوافق MySQL + PostgreSQL
            $addedId       = Status::where('name', 'added')->value('id');
            $underReviewId = Status::where('name', 'under_review')->value('id');

            // ✅ المضافة حديثاً أولاً، ثم قيد المراجعة، ثم البقية حسب تاريخ الإنشاء
            $query->orderByRaw(
                'CASE 
                    WHEN status_id = ? THEN 1
                    WHEN status_id = ? THEN 2
                    ELSE 3
                END ASC',
                [$addedId ?? 0, $underReviewId ?? 0]
            )->orderBy('created_at', 'desc');
        } else {
            // مدير المسرح والأدوار الأخرى: الأحدث أولاً
            $query->orderBy('created_at', 'desc');
        }

        $events = $query->paginate(10);

        // ✨ 🆕 الحالات المعروضة في الفلتر (بعد حذف under_review)
        // ملاحظة: نرتّب في PHP بدل SQL لتوافق MySQL + PostgreSQL
        $statusesCollection = Status::whereIn('name', self::VISIBLE_STATUSES)->get();

        // ترتيب حسب VISIBLE_STATUSES (متوافق مع كل قواعد البيانات)
        $allStatuses = collect(self::VISIBLE_STATUSES)
            ->map(fn($name) => $statusesCollection->firstWhere('name', $name))
            ->filter()
            ->values();

        // ✨ اقتراحات Autocomplete (مع فلترة الدور)
        $suggestions = [];
        if ($this->showSuggestions && !empty($this->searchTitle)) {
            $suggestionQuery = Event::where('title', 'like', '%' . $this->searchTitle . '%');

            // مدير المسرح يشوف اقتراحات فعالياته فقط
            if ($roleName === 'theater_manager') {
                $suggestionQuery->where('created_by', Auth::id());
            }
            // ✅ مدير الإعلام لا يشوف اقتراحات للمسودات
            elseif ($roleName === 'event_manager') {
                $draftStatusId = Status::where('name', 'draft')->value('id');
                if ($draftStatusId) {
                    $suggestionQuery->where('status_id', '!=', $draftStatusId);
                }
            }

            $suggestions = $suggestionQuery->orderBy('created_at', 'desc')
                ->limit(8)
                ->pluck('title')
                ->unique()
                ->values()
                ->toArray();
        }

        return view('livewire.dashboard.events', [
            'events'      => $events,
            'roleName'    => $roleName,
            'allStatuses' => $allStatuses,
            'suggestions' => $suggestions,
        ]);
    }
}
