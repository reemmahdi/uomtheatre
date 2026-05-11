<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * VipBooking — UOMTheatre (إعادة هندسة - مقاعد متغيرة per-event)
 * ════════════════════════════════════════════════════════════════
 *
 * 🎯 التغيير المعماري:
 *   - مدير الإعلام يرى كل الـ 997 مقعد (بدل 52 ثابتة)
 *   - يضغط على أي مقعد متاح → يحجز كوفد
 *   - المقاعد المحجوزة كوفود = ملونة باسم الضيف
 *   - المقاعد المحجوزة من الجمهور = ملونة بلون مختلف (لو الفعالية منشورة سابقاً)
 *   - المتاحة = قابلة للضغط
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('حجز مقاعد الوفود')]
class VipBooking extends BaseComponent
{
    public int $eventId;
    public string $eventUuid = '';

    // ==================== حقول الحجز الجديد ====================
    public string $guestName = '';
    public string $guestPhone = '';
    public int $selectedSeatId = 0;

    // ==================== حقول التعديل ====================
    public ?int $editBookingId = null;
    public string $editGuestName = '';
    public string $editGuestPhone = '';

    // ==================== حقول العرض ====================
    public ?array $viewBooking = null;

    // ════════════════════════════════════════════════════════════
    // ✨ Helpers أمنية
    // ════════════════════════════════════════════════════════════

    protected function authorizeManageVip(): void
    {
        $event = Event::findOrFail($this->eventId);
        if (!Auth::user()?->can('manageVipSeats', $event)) {
            abort(403, 'غير مصرح لك بإدارة مقاعد الوفود لهذه الفعالية');
        }
    }

    protected function getReservationForThisEvent(int $reservationId): Reservation
    {
        $res = Reservation::with(['seat.section', 'event'])->findOrFail($reservationId);
        if ($res->event_id !== $this->eventId) {
            abort(403, 'هذا الحجز لا يخص هذه الفعالية');
        }
        return $res;
    }

    public function mount(string $eventUuid)
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $eventUuid)) {
            abort(404, 'معرّف الفعالية غير صحيح');
        }

        $event = Event::where('uuid', $eventUuid)->firstOrFail();
        $this->eventUuid = $eventUuid;
        $this->eventId   = $event->id;

        $this->authorizeManageVip();
    }

    // ==================== اختيار مقعد ====================
    public function selectSeat(int $seatId): void
    {
        $this->authorizeManageVip();

        $event = Event::findOrFail($this->eventId);
        if ($event->is_booking_paused) {
            $this->swalError('الحجز موقوف مؤقتاً لهذه الفعالية. لا يمكن إضافة حجوزات جديدة.');
            return;
        }

        // 🎯 جديد: التأكد من أن المقعد متاح فعلاً (مش محجوز من قبل)
        $seat = Seat::find($seatId);
        if (!$seat) {
            $this->swalError('المقعد غير موجود');
            return;
        }

        if ($seat->isReservedForEvent($this->eventId)) {
            $this->swalError('هذا المقعد محجوز مسبقاً');
            return;
        }

        $this->selectedSeatId = $seatId;
        $this->guestName  = '';
        $this->guestPhone = '';
    }

    // ==================== حجز مقعد ====================
    public function bookSeat(): void
    {
        $this->authorizeManageVip();

        $this->validate([
            'guestName'  => 'required|string|max:255',
            'guestPhone' => 'required|string|min:10',
        ], [
            'guestName.required'  => 'اسم الضيف مطلوب',
            'guestPhone.required' => 'رقم الجوال مطلوب',
            'guestPhone.min'      => 'رقم الجوال غير صحيح',
        ]);

        try {
            $reservation = DB::transaction(function () {
                $event = Event::lockForUpdate()->findOrFail($this->eventId);

                if ($event->is_booking_paused) {
                    throw new \RuntimeException('الحجز موقوف مؤقتاً.');
                }

                $seat = Seat::lockForUpdate()->findOrFail($this->selectedSeatId);

                $existing = Reservation::where('event_id', $this->eventId)
                    ->where('seat_id', $this->selectedSeatId)
                    ->where('status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $msg = $existing->type === 'vip_guest'
                        ? 'هذا المقعد محجوز للوفد ' . ($existing->guest_name ?? 'ضيف')
                        : 'هذا المقعد محجوز من قبل الجمهور';
                    throw new \RuntimeException($msg);
                }

                return Reservation::create([
                    'user_id'     => Auth::id(),
                    'event_id'    => $this->eventId,
                    'seat_id'     => $this->selectedSeatId,
                    'status'      => 'confirmed',
                    'type'        => 'vip_guest',
                    'guest_name'  => $this->guestName,
                    'guest_phone' => $this->guestPhone,
                ]);
            });

            $seat = Seat::find($this->selectedSeatId);
            $this->dispatch('new-booking-created', reservationId: $reservation->id);
            $this->swalSuccess('تم حجز المقعد ' . ($seat?->label ?? '') . ' للضيف ' . $this->guestName);
            $this->reset(['guestName', 'guestPhone', 'selectedSeatId']);
            $this->dispatch('close-modal');
        } catch (\RuntimeException $e) {
            $this->swalError($e->getMessage());
        } catch (\Exception $e) {
            $this->swalError('فشل الحجز: ' . $e->getMessage());
        }
    }

    // ==================== فتح نافذة العرض ====================
    public function openViewBooking(int $reservationId): void
    {
        $this->authorizeManageVip();
        $res = $this->getReservationForThisEvent($reservationId);

        $this->viewBooking = [
            'id'           => $res->id,
            'guest_name'   => $res->guest_name,
            'guest_phone'  => $res->guest_phone,
            'seat_label'   => $res->seat?->label,
            'section_name' => $res->seat?->section?->name,
            'row_number'   => $res->seat?->row_number,
            'seat_number'  => $res->seat?->seat_number,
            'created_at'   => $res->created_at?->format('Y-m-d H:i'),
            'qr_code'      => $res->qr_code,
        ];

        $this->dispatch('open-modal', id: 'viewBookingModal');
    }

    // ==================== فتح نافذة التعديل ====================
    public function openEditBooking(int $reservationId): void
    {
        $this->authorizeManageVip();
        $res = $this->getReservationForThisEvent($reservationId);

        $this->editBookingId  = $res->id;
        $this->editGuestName  = $res->guest_name ?? '';
        $this->editGuestPhone = $res->guest_phone ?? '';

        $this->dispatch('open-modal', id: 'editBookingModal');
    }

    // ==================== حفظ التعديلات ====================
    public function updateBooking(): void
    {
        $this->authorizeManageVip();

        $this->validate([
            'editGuestName'  => 'required|string|max:255',
            'editGuestPhone' => 'required|string|min:10',
        ], [
            'editGuestName.required'  => 'اسم الضيف مطلوب',
            'editGuestPhone.required' => 'رقم الجوال مطلوب',
            'editGuestPhone.min'      => 'رقم الجوال غير صحيح',
        ]);

        try {
            $res = $this->getReservationForThisEvent((int) $this->editBookingId);
            $oldName = $res->guest_name;

            $res->update([
                'guest_name'  => $this->editGuestName,
                'guest_phone' => $this->editGuestPhone,
            ]);

            $this->swalSuccess('تم تحديث بيانات الضيف ' . $oldName);
            $this->reset(['editBookingId', 'editGuestName', 'editGuestPhone']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل التعديل: ' . $e->getMessage());
        }
    }

    // ==================== طلب تأكيد إلغاء الحجز ====================
    public function requestCancelBooking(int $reservationId): void
    {
        $this->authorizeManageVip();
        $res = $this->getReservationForThisEvent($reservationId);

        $this->swalConfirm(
            message: "هل أنت متأكد من إلغاء حجز \"{$res->guest_name}\"؟",
            action:  'confirmCancelBooking',
            params:  $reservationId,
            title:   'تأكيد الإلغاء'
        );
    }

    #[On('confirmCancelBooking')]
    public function confirmCancelBooking($id): void
    {
        $this->authorizeManageVip();

        try {
            $res = $this->getReservationForThisEvent((int) $id);
            $name = $res->guest_name;
            $res->update(['status' => 'cancelled']);

            $this->swalToast('تم إلغاء حجز ' . $name);
        } catch (\Exception $e) {
            $this->swalError('فشل الإلغاء: ' . $e->getMessage());
        }
    }

    // ==================== جلب الجالسين في 4 جهات ====================
    private function getNeighbors(int $eventId, $seat): array
    {
        if (!$seat) return [];

        $neighbors = [];
        $directions = [
            'right' => ['col' => $seat->seat_number - 1, 'row' => $seat->row_number,     'label' => 'على اليمين'],
            'left'  => ['col' => $seat->seat_number + 1, 'row' => $seat->row_number,     'label' => 'على اليسار'],
            'front' => ['col' => $seat->seat_number,     'row' => $seat->row_number - 1, 'label' => 'أمام'],
            'back'  => ['col' => $seat->seat_number,     'row' => $seat->row_number + 1, 'label' => 'خلف'],
        ];

        foreach ($directions as $key => $dir) {
            $neighbor = Reservation::with('seat')
                ->where('event_id', $eventId)
                ->where('status', '!=', 'cancelled')
                ->where('type', 'vip_guest')   // 🎯 جيران الوفود فقط (بأسماء)
                ->whereHas('seat', fn($q) => $q
                    ->where('section_id', $seat->section_id)
                    ->where('row_number', $dir['row'])
                    ->where('seat_number', $dir['col']))
                ->first();

            if ($neighbor) {
                $neighbors[$key] = [
                    'label' => $dir['label'],
                    'name'  => $neighbor->guest_name ?? 'ضيف',
                ];
            }
        }

        return $neighbors;
    }

    // ==================== رابط واتساب رسمي ====================
    public function getWhatsAppLink(int $reservationId): string
    {
        $this->authorizeManageVip();

        $res   = $this->getReservationForThisEvent($reservationId);
        $event = $res->event;
        $seat  = $res->seat;

        if (!$event || !$seat) return '';

        $neighbors = $this->getNeighbors($this->eventId, $seat);

        $invitationUrl = route('invitation.show', $res->qr_code);
        if (!str_starts_with($invitationUrl, 'http://') && !str_starts_with($invitationUrl, 'https://')) {
            $invitationUrl = 'https://' . ltrim($invitationUrl, '/');
        }

        $startTime = $event->start_datetime?->format('h:i') ?? '';
        $period = $event->start_datetime?->format('A') === 'AM' ? 'صباحاً' : 'مساءً';

        $msg  = "جامعة الموصل - مسرح الجامعة\n";
        $msg .= "─────────────────────────\n\n";
        $msg .= "السلام عليكم ورحمة الله وبركاته\n\n";
        $msg .= "الأستاذ/ة الفاضل/ة: {$res->guest_name}\n\n";
        $msg .= "تحية طيبة وبعد،\n\n";
        $msg .= "يسعدنا دعوتكم لحضور الفعالية الموسومة بـ:\n";
        $msg .= "{$event->title}\n\n";
        $msg .= "والتي ستقام بتاريخ " . ($event->start_datetime?->format('Y-m-d') ?? '');
        $msg .= " في تمام الساعة {$startTime} {$period}،\n";
        $msg .= "على مسرح جامعة الموصل.\n\n";
        $msg .= "معلومات مقعدكم:\n";
        $msg .= "- القسم: " . ($seat->section?->name ?? '') . "\n";
        $msg .= "- الصف: {$seat->row_number}\n";
        $msg .= "- رقم المقعد: {$seat->seat_number}\n";
        $msg .= "- الرمز: {$seat->label}\n\n";

        if (!empty($neighbors)) {
            $msg .= "الجالسون بجانبكم:\n";
            foreach ($neighbors as $n) {
                $msg .= "- {$n['label']}: {$n['name']}\n";
            }
            $msg .= "\n";
        }

        $msg .= "للاطلاع على دعوتكم الإلكترونية ورمز الدخول (QR Code):\n";
        $msg .= $invitationUrl . "\n\n";

        $msg .= "نتشرف بحضوركم الكريم،،،\n\n";
        $msg .= "تفضلوا بقبول فائق الاحترام والتقدير،،،\n\n";
        $msg .= "إدارة مسرح جامعة الموصل";

        $phone = preg_replace('/[^0-9]/', '', $res->guest_phone ?? '');
        if (str_starts_with($phone, '0')) {
            $phone = '964' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
    }

    // ====================================================================
    // 🎯 Render — يجلب كل المقاعد + كل الحجوزات
    // ====================================================================
    public function render()
    {
        $this->authorizeManageVip();

        $event = Event::with('status')->findOrFail($this->eventId);

        // ✨ جديد: جلب كل المقاعد (الـ 997) مرتبة حسب القسم/الصف/الرقم
        $allSeats = Seat::with('section')
            ->orderBy('section_id')
            ->orderBy('row_number')
            ->orderBy('seat_number')
            ->get();

        // ✨ جديد: جلب كل الحجوزات النشطة (وفود + جمهور) - keyed by seat_id
        $allReservations = Reservation::where('event_id', $this->eventId)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->keyBy('seat_id');

        // ✨ تجميع حسب القسم → الصف → المقاعد
        $seatsBySection = $allSeats->groupBy('section.name');

        // إحصائيات
        $stats = [
            'total_seats'     => $allSeats->count(),
            'vip_booked'      => $allReservations->where('type', 'vip_guest')->count(),
            'public_reserved' => $allReservations->where('type', '!=', 'vip_guest')->count(),
            'available'       => $allSeats->count() - $allReservations->count(),
        ];

        return view('livewire.dashboard.vip-booking', [
            'event'           => $event,
            'seatsBySection'  => $seatsBySection,
            'allReservations' => $allReservations,
            'stats'           => $stats,
        ]);
    }
}
