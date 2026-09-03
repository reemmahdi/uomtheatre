<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\EventLog;
use App\Models\Status;
use App\Services\EventApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الفعاليات')]
class Events extends BaseComponent
{
    protected array $allowedRoles = ['super_admin', 'theater_manager', 'event_manager'];

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $title = '';
    public string $description = '';
    public string $start_date = '';
    public string $start_time = '';
    public string $end_date = '';
    public string $end_time = '';

    public string $start_hour = '';
    public string $start_minute = '';
    public string $start_period = '';
    public string $end_hour = '';
    public string $end_minute = '';
    public string $end_period = '';

    public int $editId = 0;
    public string $editTitle = '';
    public string $editDescription = '';
    public string $editStartDate = '';
    public string $editStartTime = '';
    public string $editEndDate = '';
    public string $editEndTime = '';

    public string $editStartHour = '';
    public string $editStartMinute = '';
    public string $editStartPeriod = '';
    public string $editEndHour = '';
    public string $editEndMinute = '';
    public string $editEndPeriod = '';

    public int $cancelEventId = 0;
    public string $cancelReason = '';
    public bool $isCancelingPublished = false;
    public string $cancelEventTitle = '';
    public int $cancelReservationsCount = 0;

    public int $postponeEventId = 0;
    public string $postponeEventTitle = '';
    public int $postponeReservationsCount = 0;
    public string $postponeTitle = '';
    public string $postponeDescription = '';
    public string $postponeStartDate = '';
    public string $postponeStartTime = '';
    public string $postponeEndDate = '';
    public string $postponeEndTime = '';
    public string $postponeStartHour = '';
    public string $postponeStartMinute = '';
    public string $postponeStartPeriod = '';
    public string $postponeEndHour = '';
    public string $postponeEndMinute = '';
    public string $postponeEndPeriod = '';
    public string $postponeReason = '';

    public array $showEvent = [];

    public string $searchTitle = '';
    public string $filterStatus = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public bool $showSuggestions = false;

    private const VISIBLE_STATUSES = [
        'draft',
        'added',
        'active',
        'published',
        'closed',
        'cancelled',
        'end',
    ];

    public function resetFilters(): void
    {
        $this->reset(['searchTitle', 'filterStatus', 'filterDateFrom', 'filterDateTo']);
        $this->showSuggestions = false;
        $this->resetPage();

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

    private function combineDateTime(string $date, string $time): string
    {
        if (strlen($time) === 5) {
            $time .= ':00';
        }
        return $date . ' ' . $time;
    }

    private function buildTime24(string $hour, string $minute, string $period): string
    {
        if (empty($hour) || empty($minute) || empty($period)) {
            return '';
        }
        $h = (int) $hour;

        if ($period === 'AM') {
            if ($h === 12) $h = 0;
        } else {
            if ($h !== 12) $h += 12;
        }
        return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . $minute;
    }

    public function updatedStartHour(): void   { $this->syncStartTime(); }
    public function updatedStartMinute(): void { $this->syncStartTime(); }
    public function updatedStartPeriod(): void { $this->syncStartTime(); }
    public function updatedEndHour(): void     { $this->syncEndTime(); }
    public function updatedEndMinute(): void   { $this->syncEndTime(); }
    public function updatedEndPeriod(): void   { $this->syncEndTime(); }

    private function syncStartTime(): void
    {
        $this->start_time = $this->buildTime24($this->start_hour, $this->start_minute, $this->start_period);
    }
    private function syncEndTime(): void
    {
        $this->end_time = $this->buildTime24($this->end_hour, $this->end_minute, $this->end_period);
    }

    public function updatedEditStartHour(): void   { $this->syncEditStartTime(); }
    public function updatedEditStartMinute(): void { $this->syncEditStartTime(); }
    public function updatedEditStartPeriod(): void { $this->syncEditStartTime(); }
    public function updatedEditEndHour(): void     { $this->syncEditEndTime(); }
    public function updatedEditEndMinute(): void   { $this->syncEditEndTime(); }
    public function updatedEditEndPeriod(): void   { $this->syncEditEndTime(); }

    private function syncEditStartTime(): void
    {
        $this->editStartTime = $this->buildTime24($this->editStartHour, $this->editStartMinute, $this->editStartPeriod);
    }
    private function syncEditEndTime(): void
    {
        $this->editEndTime = $this->buildTime24($this->editEndHour, $this->editEndMinute, $this->editEndPeriod);
    }

    public function updatedPostponeStartHour(): void   { $this->syncPostponeStartTime(); }
    public function updatedPostponeStartMinute(): void { $this->syncPostponeStartTime(); }
    public function updatedPostponeStartPeriod(): void { $this->syncPostponeStartTime(); }
    public function updatedPostponeEndHour(): void     { $this->syncPostponeEndTime(); }
    public function updatedPostponeEndMinute(): void   { $this->syncPostponeEndTime(); }
    public function updatedPostponeEndPeriod(): void   { $this->syncPostponeEndTime(); }

    private function syncPostponeStartTime(): void
    {
        $this->postponeStartTime = $this->buildTime24($this->postponeStartHour, $this->postponeStartMinute, $this->postponeStartPeriod);
    }
    private function syncPostponeEndTime(): void
    {
        $this->postponeEndTime = $this->buildTime24($this->postponeEndHour, $this->postponeEndMinute, $this->postponeEndPeriod);
    }

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
            $this->reset(['title', 'description', 'start_date', 'start_time', 'end_date', 'end_time',
                          'start_hour', 'start_minute', 'start_period',
                          'end_hour', 'end_minute', 'end_period']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل إنشاء الفعالية: ' . $e->getMessage());
        }
    }

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

        $formatArabic12 = function ($dt) {
            if (!$dt) return null;
            $h12 = $dt->format('g');
            $min = $dt->format('i');
            $period = $dt->format('A') === 'AM' ? 'صباحاً' : 'مساءً';
            return $dt->format('Y-m-d') . ' - ' . $h12 . ':' . $min . ' ' . $period;
        };

        $this->showEvent = [
            'title'               => $event->title,
            'description'         => $event->description ?? 'لا يوجد وصف',
            'start_datetime'      => $formatArabic12($event->start_datetime),
            'end_datetime'        => $formatArabic12($event->end_datetime),
            'duration'            => $durationText,
            'status'              => $event->status->display_name,
            'status_name'         => $event->status->name,
            'created_by'          => $event->creator->name,
            'created_at'          => $formatArabic12($event->created_at),
            'published_at'        => $event->published_at ? $formatArabic12($event->published_at) : 'لم تنشر بعد',
            'cancellation_reason' => $event->cancellation_reason,
            'cancelled_at'        => $event->cancelled_at ? $formatArabic12($event->cancelled_at) : null,
            'is_booking_paused'   => $event->is_booking_paused,
            'paused_at'           => $event->paused_at ? $formatArabic12($event->paused_at) : null,
        ];

        $this->dispatch('open-view-modal');
    }

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

        $this->splitTime12($event->start_datetime, 'editStart');
        $this->splitTime12($event->end_datetime, 'editEnd');

        $this->dispatch('open-edit-modal');
    }

    private function splitTime12($datetime, string $prefix): void
    {
        if (!$datetime) return;
        $h24 = (int) $datetime->format('H');
        $min = $datetime->format('i');
        $period = $h24 >= 12 ? 'PM' : 'AM';
        $h12 = $h24 % 12;
        if ($h12 === 0) $h12 = 12;

        $this->{$prefix . 'Hour'}   = (string) $h12;
        $this->{$prefix . 'Minute'} = $min;
        $this->{$prefix . 'Period'} = $period;
    }

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

        $datesChanged = $startDatetime !== $event->start_datetime->format('Y-m-d H:i:s')
            || $endDatetime !== $event->end_datetime->format('Y-m-d H:i:s');
        if ($datesChanged && $event->status?->name === 'published') {
            $this->swalError('الفعالية منشورة وقد تحمل حجوزات — لتغيير موعدها استخدم زر "تأجيل" حتى يُشعَر الحاجزون ويُمهَلوا 24 ساعة للتأكيد');
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

    public function openPostpone(int $eventId)
    {
        $event = Event::with('status')->findOrFail($eventId);
        $this->authorize('postpone', $event);

        if (!in_array($event->status?->name, ['active', 'published'], true)) {
            $this->swalError('يمكن تأجيل الفعاليات النشطة أو المنشورة فقط');
            return;
        }

        $this->postponeEventId    = $event->id;
        $this->postponeEventTitle = $event->title;
        $this->postponeTitle       = $event->title;
        $this->postponeDescription = $event->description ?? '';

        $this->postponeReservationsCount = $event->reservations()
            ->where('status', '!=', 'cancelled')
            ->where('type', '!=', 'vip_guest')
            ->whereNotNull('user_id')
            ->count();

        $this->postponeStartDate = $event->start_datetime->format('Y-m-d');
        $this->postponeStartTime = $event->start_datetime->format('H:i');
        $this->postponeEndDate   = $event->end_datetime->format('Y-m-d');
        $this->postponeEndTime   = $event->end_datetime->format('H:i');
        $this->splitTime12($event->start_datetime, 'postponeStart');
        $this->splitTime12($event->end_datetime, 'postponeEnd');
        $this->postponeReason = '';
    }

    public function postponeEvent()
    {
        $event = Event::with('status')->findOrFail($this->postponeEventId);
        $this->authorize('postpone', $event);
        $startRule = 'required|date';
        if ($this->postponeStartDate !== $event->start_datetime->format('Y-m-d')) {
            $startRule .= '|after_or_equal:today';
        }

        $this->validate([
            'postponeTitle'       => 'required|string|max:255',
            'postponeDescription' => 'nullable|string|max:250',
            'postponeStartDate' => $startRule,
            'postponeStartTime' => 'required|date_format:H:i',
            'postponeEndDate'   => 'required|date|after_or_equal:postponeStartDate',
            'postponeEndTime'   => 'required|date_format:H:i',
            'postponeReason'    => 'nullable|string|max:500',
        ], [
            'postponeTitle.required'           => 'عنوان الفعالية مطلوب',
            'postponeDescription.max'          => 'يجب ألا يتجاوز الوصف 250 حرف (حوالي 4 أسطر)',
            'postponeStartDate.required'       => 'تاريخ البدء الجديد مطلوب',
            'postponeStartDate.after_or_equal' => 'تاريخ البدء يجب أن يكون اليوم أو في المستقبل',
            'postponeStartTime.required'       => 'وقت البدء الجديد مطلوب',
            'postponeEndDate.required'         => 'تاريخ الانتهاء الجديد مطلوب',
            'postponeEndDate.after_or_equal'   => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'postponeEndTime.required'         => 'وقت الانتهاء الجديد مطلوب',
            'postponeReason.max'               => 'سبب التأجيل يجب ألا يتجاوز 500 حرف',
        ]);

        $startDatetime = $this->combineDateTime($this->postponeStartDate, $this->postponeStartTime);
        $endDatetime   = $this->combineDateTime($this->postponeEndDate, $this->postponeEndTime);

        $logicError = $this->validateDatetimeLogic($startDatetime, $endDatetime);
        if ($logicError) {
            $this->addError('postponeEndTime', $logicError);
            return;
        }

        $datesChanged = !($startDatetime === $event->start_datetime->format('Y-m-d H:i:s')
            && $endDatetime === $event->end_datetime->format('Y-m-d H:i:s'));
        $textChanged = $this->postponeTitle !== $event->title
            || $this->postponeDescription !== ($event->description ?? '');

        if (!$datesChanged && !$textChanged) {
            $this->swalError('لا يوجد أي تغيير — الموعد والبيانات كما هي');
            return;
        }

        try {

            $event->update([
                'title'       => $this->postponeTitle,
                'description' => $this->postponeDescription !== '' ? $this->postponeDescription : null,
            ]);

            if ($datesChanged) {
                $result = app(\App\Services\EventScheduleChangeService::class)->postpone(
                    $event,
                    \Carbon\Carbon::parse($startDatetime),
                    \Carbon\Carbon::parse($endDatetime),
                    $this->postponeReason !== '' ? $this->postponeReason : null,
                    Auth::user(),
                );

                $this->swalSuccess(
                    'تم تأجيل "' . $event->title . '" وإشعار ' . $result['notified']
                    . ' حاجزاً — لديهم 24 ساعة لتأكيد حجوزهم'
                );
            } else {
                $this->swalSuccess('تم تحديث بيانات الفعالية — الموعد لم يتغير ولم يُرسل أي إشعار');
            }

            $this->reset([
                'postponeEventId', 'postponeEventTitle', 'postponeReservationsCount',
                'postponeTitle', 'postponeDescription',
                'postponeStartDate', 'postponeStartTime', 'postponeEndDate', 'postponeEndTime',
                'postponeStartHour', 'postponeStartMinute', 'postponeStartPeriod',
                'postponeEndHour', 'postponeEndMinute', 'postponeEndPeriod',
                'postponeReason',
            ]);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل التأجيل: ' . $e->getMessage());
        }
    }

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

    public function requestChangeStatus(int $eventId, string $newStatusName)
    {
        $event = Event::find($eventId);
        $isResubmit = $event && $event->status?->name === Status::REJECTED;

        $messages = [
            'added'        => $isResubmit
                ? 'إعادة إرسال الفعالية لمكتب رئاسة الجامعة للموافقة (دورة جديدة). لن يمكنك التعديل عليها بعد الإرسال.'
                : 'سيتم إرسال الفعالية لمكتب رئاسة الجامعة للموافقة. لن يمكنك التعديل عليها بعد الإرسال.',
            'active'       => 'قبول الفعالية؟',
            'published'    => 'نشر الفعالية للجمهور؟',
            'closed'       => 'إغلاق الفعالية؟',
        ];

        $titles = [
            'added'        => $isResubmit ? 'تأكيد إعادة الإرسال' : 'تأكيد الإرسال للموافقة',
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
            $event = Event::findOrFail($eventId);

            $abilities = [
                Status::ADDED     => 'send',
                Status::ACTIVE    => 'approveAsTheater',
                Status::PUBLISHED => 'publish',
                Status::CLOSED    => 'close',
            ];
            if (!isset($abilities[$newStatusName])) {
                $this->swalError('انتقال الحالة غير مسموح');
                return;
            }
            $this->authorize($abilities[$newStatusName], $event);

            if ($newStatusName === Status::ADDED) {
                $service = app(EventApprovalService::class);
                $service->sendForApproval($event);

                $this->swalSuccess('تم إرسال الفعالية لمكتب رئاسة الجامعة للموافقة');
                return;
            }

            $oldStatusId = $event->status_id;
            $newStatus   = Status::where('name', $newStatusName)->first();

            if (!$newStatus) {
                $this->swalError('الحالة المطلوبة غير موجودة');
                return;
            }

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

    public function render()
    {
        $this->authorize('viewAny', Event::class);

        $roleName = Auth::user()->role->name;

        $this->autoEndExpiredEvents();

        $query = Event::with(['status', 'creator']);

        if ($roleName === 'event_manager') {
            $query->where('created_by', Auth::id());
        } elseif ($roleName === 'theater_manager') {
            $draftStatusId = Status::where('name', 'draft')->value('id');
            if ($draftStatusId) {
                $query->where('status_id', '!=', $draftStatusId);
            }
        } elseif ($roleName === 'university_office') {
            $draftStatusId = Status::where('name', 'draft')->value('id');
            if ($draftStatusId) {
                $query->where('status_id', '!=', $draftStatusId);
            }
        }

        if (!empty($this->searchTitle)) {
            $query->where('title', 'like', '%' . $this->searchTitle . '%');
        }

        if (!empty($this->filterStatus)) {
            $statusObj = Status::where('name', $this->filterStatus)->first();
            if ($statusObj) {
                $query->where('status_id', $statusObj->id);
            }
        }

        if (!empty($this->filterDateFrom)) {
            $query->whereDate('start_datetime', '>=', $this->filterDateFrom);
        }

        if (!empty($this->filterDateTo)) {
            $query->whereDate('start_datetime', '<=', $this->filterDateTo);
        }

        if ($roleName === 'event_manager') {
            $draftId  = Status::where('name', 'draft')->value('id');
            $activeId = Status::where('name', 'active')->value('id');

            $query->orderByRaw(
                'CASE
                    WHEN status_id = ? THEN 1
                    WHEN status_id = ? THEN 2
                    ELSE 3
                END ASC',
                [$draftId ?? 0, $activeId ?? 0]
            )->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $events = $query->paginate(10);

        $statusesCollection = Status::whereIn('name', self::VISIBLE_STATUSES)->get();

        $allStatuses = collect(self::VISIBLE_STATUSES)
            ->map(fn($name) => $statusesCollection->firstWhere('name', $name))
            ->filter()
            ->values();

        $suggestions = [];
        if ($this->showSuggestions && !empty($this->searchTitle)) {
            $suggestionQuery = Event::where('title', 'like', '%' . $this->searchTitle . '%');

            if ($roleName === 'event_manager') {
                $suggestionQuery->where('created_by', Auth::id());
            }

            elseif (in_array($roleName, ['theater_manager', 'university_office'])) {
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
