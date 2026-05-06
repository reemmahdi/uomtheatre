<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
#[Title('حجز مقاعد الوفود')]
class VipBooking extends BaseComponent
{
    public int $eventId;
    public string $eventUuid = '';  // ✨ جديد: للحماية ضد IDOR

    // ==================== حقول الحجز الجديد ====================
    public string $guestName = '';
    public string $guestPhone = '';
    public int $selectedSeatId = 0;

    // ==================== ✨ حقول التعديل ====================
    public ?int $editBookingId = null;
    public string $editGuestName = '';
    public string $editGuestPhone = '';

    // ==================== ✨ حقول العرض ====================
    public ?array $viewBooking = null;

    /**
     * ✨ تعديل: يستقبل UUID بدل ID رقمي (حماية ضد IDOR)
     *
     * URL: /dashboard/events/{eventUuid}/vip-booking
     * مثال: /dashboard/events/9f1d2c4a-3b5e-4f78-9c12-abcd1234ef56/vip-booking
     */
    public function mount(string $eventUuid)
    {
        // 🛡️ التحقق من شكل UUID صحيح (احتياطي - الـ Route يفلتره أيضاً)
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $eventUuid)) {
            abort(404, 'معرّف الفعالية غير صحيح');
        }

        // 🛡️ البحث عن الفعالية بـ UUID (لو غير موجودة، 404)
        $event = Event::where('uuid', $eventUuid)->firstOrFail();

        $this->eventUuid = $eventUuid;
        $this->eventId = $event->id;  // الـ ID الرقمي يبقى داخلياً للأداء
    }

    // ==================== اختيار مقعد ====================
    public function selectSeat(int $seatId)
    {
        $event = Event::findOrFail($this->eventId);
        if ($event->is_booking_paused) {
            $this->swalError('الحجز موقوف مؤقتاً لهذه الفعالية. لا يمكن إضافة حجوزات جديدة.');
            return;
        }

        $this->selectedSeatId = $seatId;
        $this->guestName = '';
        $this->guestPhone = '';
    }

    // ==================== حجز مقعد ====================
    public function bookSeat()
    {
        $this->validate([
            'guestName' => 'required|string|max:255',
            'guestPhone' => 'required|string|min:10',
        ], [
            'guestName.required' => 'اسم الضيف مطلوب',
            'guestPhone.required' => 'رقم الجوال مطلوب',
            'guestPhone.min' => 'رقم الجوال غير صحيح',
        ]);

        try {
            $event = Event::findOrFail($this->eventId);
            if ($event->is_booking_paused) {
                $this->swalError('الحجز موقوف مؤقتاً. لا يمكن إتمام الحجز.');
                $this->dispatch('close-modal');
                return;
            }

            $seat = Seat::findOrFail($this->selectedSeatId);

            $existing = Reservation::where('event_id', $this->eventId)
                ->where('seat_id', $this->selectedSeatId)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existing) {
                $this->swalError('هذا المقعد محجوز لـ ' . ($existing->guest_name ?? 'ضيف'));
                return;
            }

            $reservation = Reservation::create([
                'user_id' => Auth::id(),
                'event_id' => $this->eventId,
                'seat_id' => $this->selectedSeatId,
                'status' => 'confirmed',
                'type' => 'vip_guest',
                'guest_name' => $this->guestName,
                'guest_phone' => $this->guestPhone,
            ]);

            $this->dispatch('new-booking-created', reservationId: $reservation->id);

            $this->swalSuccess('تم حجز المقعد ' . $seat->label . ' للضيف ' . $this->guestName);
            $this->reset(['guestName', 'guestPhone', 'selectedSeatId']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل الحجز: ' . $e->getMessage());
        }
    }

    // ==================== ✨ 🆕 فتح نافذة العرض ====================
    public function openViewBooking(int $reservationId)
    {
        $res = Reservation::with(['seat.section', 'event'])->findOrFail($reservationId);

        $this->viewBooking = [
            'id'           => $res->id,
            'guest_name'   => $res->guest_name,
            'guest_phone'  => $res->guest_phone,
            'seat_label'   => $res->seat->label,
            'section_name' => $res->seat->section->name,
            'row_number'   => $res->seat->row_number,
            'seat_number'  => $res->seat->seat_number,
            'created_at'   => $res->created_at->format('Y-m-d H:i'),
            'qr_code'      => $res->qr_code,
        ];

        $this->dispatch('open-modal', id: 'viewBookingModal');
    }

    // ==================== ✨ 🆕 فتح نافذة التعديل ====================
    public function openEditBooking(int $reservationId)
    {
        $res = Reservation::findOrFail($reservationId);

        $this->editBookingId   = $res->id;
        $this->editGuestName   = $res->guest_name ?? '';
        $this->editGuestPhone  = $res->guest_phone ?? '';

        $this->dispatch('open-modal', id: 'editBookingModal');
    }

    // ==================== ✨ 🆕 حفظ التعديلات ====================
    public function updateBooking()
    {
        $this->validate([
            'editGuestName'  => 'required|string|max:255',
            'editGuestPhone' => 'required|string|min:10',
        ], [
            'editGuestName.required'  => 'اسم الضيف مطلوب',
            'editGuestPhone.required' => 'رقم الجوال مطلوب',
            'editGuestPhone.min'      => 'رقم الجوال غير صحيح',
        ]);

        try {
            $res = Reservation::findOrFail($this->editBookingId);
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
    public function requestCancelBooking(int $reservationId)
    {
        $res = Reservation::findOrFail($reservationId);

        $this->swalConfirm(
            message: "هل أنت متأكد من إلغاء حجز \"{$res->guest_name}\"؟",
            action: 'confirmCancelBooking',
            params: $reservationId,
            title: 'تأكيد الإلغاء'
        );
    }

    // ==================== تنفيذ الإلغاء بعد التأكيد ====================
    #[On('confirmCancelBooking')]
    public function confirmCancelBooking($id)
    {
        try {
            $res = Reservation::findOrFail($id);
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
        $neighbors = [];
        $directions = [
            'right' => ['col' => $seat->seat_number - 1, 'row' => $seat->row_number, 'label' => 'على اليمين'],
            'left'  => ['col' => $seat->seat_number + 1, 'row' => $seat->row_number, 'label' => 'على اليسار'],
            'front' => ['col' => $seat->seat_number,     'row' => $seat->row_number - 1, 'label' => 'أمام'],
            'back'  => ['col' => $seat->seat_number,     'row' => $seat->row_number + 1, 'label' => 'خلف'],
        ];

        foreach ($directions as $key => $dir) {
            $neighbor = Reservation::with('seat')
                ->where('event_id', $eventId)
                ->where('status', 'confirmed')
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
        $res = Reservation::with(['event', 'seat.section'])->findOrFail($reservationId);
        $event = $res->event;
        $seat = $res->seat;

        $neighbors = $this->getNeighbors($this->eventId, $seat);

        // ✨ توليد الرابط بصيغة كاملة (https) - حتى ينعرض clickable في الواتساب
        $invitationUrl = route('invitation.show', $res->qr_code);

        // ضمان أن الرابط يبدأ بـ http/https (الواتساب لا يحوّل localhost أو الروابط النسبية)
        if (!str_starts_with($invitationUrl, 'http://') && !str_starts_with($invitationUrl, 'https://')) {
            $invitationUrl = 'https://' . ltrim($invitationUrl, '/');
        }

        // ✨ تنسيق الوقت بصيغة عربية (صباحاً/مساءً)
        $startTime = $event->start_datetime->format('h:i');
        $period = $event->start_datetime->format('A') === 'AM' ? 'صباحاً' : 'مساءً';

        $msg  = "جامعة الموصل - مسرح الجامعة\n";
        $msg .= "─────────────────────────\n\n";
        $msg .= "السلام عليكم ورحمة الله وبركاته\n\n";
        $msg .= "الأستاذ/ة الفاضل/ة: {$res->guest_name}\n\n";
        $msg .= "تحية طيبة وبعد،\n\n";
        $msg .= "يسعدنا دعوتكم لحضور الفعالية الموسومة بـ:\n";
        $msg .= "{$event->title}\n\n";
        $msg .= "والتي ستقام بتاريخ " . $event->start_datetime->format('Y-m-d');
        $msg .= " في تمام الساعة {$startTime} {$period}،\n";
        $msg .= "على مسرح جامعة الموصل.\n\n";
        $msg .= "معلومات مقعدكم:\n";
        $msg .= "- القسم: {$seat->section->name}\n";
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

        // ✨ الرابط بصيغة قابلة للضغط (سطر مستقل + لا فواصل غريبة قبله/بعده)
        $msg .= "للاطلاع على دعوتكم الإلكترونية ورمز الدخول (QR Code):\n";
        $msg .= $invitationUrl . "\n\n";

        $msg .= "نتشرف بحضوركم الكريم،،،\n\n";
        $msg .= "تفضلوا بقبول فائق الاحترام والتقدير،،،\n\n";
        $msg .= "إدارة مسرح جامعة الموصل";

        $phone = preg_replace('/[^0-9]/', '', $res->guest_phone);
        if (str_starts_with($phone, '0')) {
            $phone = '964' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
    }

    // ==================== Render ====================
    public function render()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin', 'event_manager'])) {
            return redirect()->route('dashboard');
        }

        $event = Event::with('status')->findOrFail($this->eventId);
        $vipSeats = Seat::with('section')
            ->where('is_vip_reserved', true)
            ->orderBy('section_id')
            ->orderBy('seat_number')
            ->get();
        $bookings = Reservation::with(['seat.section'])
            ->where('event_id', $this->eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get()
            ->keyBy('seat_id');

        return view('livewire.dashboard.vip-booking', [
            'event' => $event,
            'vipSeats' => $vipSeats,
            'bookings' => $bookings,
        ]);
    }
}
