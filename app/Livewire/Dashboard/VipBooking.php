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
    public string $guestName = '';
    public string $guestPhone = '';
    public int $selectedSeatId = 0;

    public function mount(int $id)
    {
        $this->eventId = $id;
    }

    // ==================== اختيار مقعد ====================
    public function selectSeat(int $seatId)
    {
        // فحص الإيقاف
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
            // فحص ثاني للإيقاف
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

            Reservation::create([
                'user_id' => Auth::id(),
                'event_id' => $this->eventId,
                'seat_id' => $this->selectedSeatId,
                'status' => 'confirmed',
                'type' => 'vip_guest',
                'guest_name' => $this->guestName,
                'guest_phone' => $this->guestPhone,
            ]);

            $this->swalSuccess('تم حجز المقعد ' . $seat->label . ' للضيف ' . $this->guestName);
            $this->reset(['guestName', 'guestPhone', 'selectedSeatId']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل الحجز: ' . $e->getMessage());
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

    // ==================== ✨ 🆕 جلب الجالسين في 4 جهات ====================
    /**
     * يبحث عن المقاعد المجاورة في 4 جهات (يمين، يسار، أمام، خلف)
     * ويرجع أسماء الجالسين فيها (إن وجدوا).
     *
     * شبيه: مثل خريطة شطرنج - مقعد المركز ومحيطه الفوري
     */
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

    // ==================== ✨ 🆕 رابط واتساب رسمي + رابط دعوة ====================
    public function getWhatsAppLink(int $reservationId): string
    {
        $res = Reservation::with(['event', 'seat.section'])->findOrFail($reservationId);
        $event = $res->event;
        $seat = $res->seat;

        // الجالسون في 4 جهات
        $neighbors = $this->getNeighbors($this->eventId, $seat);

        // رابط صفحة الدعوة (يحوي QR والتفاصيل الكاملة)
        $invitationUrl = route('invitation.show', $res->qr_code);

        // ═══════════════════════════════════════════
        // ✨ الرسالة الرسمية الجديدة (بدون رموز إيموجي)
        // ═══════════════════════════════════════════
        $msg  = "جامعة الموصل - مسرح الجامعة\n";
        $msg .= "─────────────────────────\n\n";

        $msg .= "السلام عليكم ورحمة الله وبركاته\n\n";

        $msg .= "الأستاذ/ة الفاضل/ة: {$res->guest_name}\n\n";

        $msg .= "تحية طيبة وبعد،\n\n";

        $msg .= "يسعدنا دعوتكم لحضور الفعالية الموسومة بـ:\n";
        $msg .= "{$event->title}\n\n";

        $msg .= "والتي ستقام بتاريخ " . $event->start_datetime->format('Y-m-d');
        $msg .= " في تمام الساعة " . $event->start_datetime->format('H:i') . "،\n";
        $msg .= "على مسرح جامعة الموصل.\n\n";

        // معلومات المقعد
        $msg .= "معلومات مقعدكم:\n";
        $msg .= "- القسم: {$seat->section->name}\n";
        $msg .= "- الصف: {$seat->row_number}\n";
        $msg .= "- رقم المقعد: {$seat->seat_number}\n";
        $msg .= "- الرمز: {$seat->label}\n\n";

        // الجالسون بجانبكم
        if (!empty($neighbors)) {
            $msg .= "الجالسون بجانبكم:\n";
            foreach ($neighbors as $n) {
                $msg .= "- {$n['label']}: {$n['name']}\n";
            }
            $msg .= "\n";
        }

        // رابط الدعوة الإلكترونية
        $msg .= "للاطلاع على دعوتكم الإلكترونية ورمز الدخول (QR Code):\n";
        $msg .= "{$invitationUrl}\n\n";

        $msg .= "نتشرف بحضوركم الكريم،،،\n\n";

        $msg .= "تفضلوا بقبول فائق الاحترام والتقدير،،،\n\n";

        $msg .= "إدارة مسرح جامعة الموصل";

        // تنسيق رقم الجوال للعراق
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
            ->get()
            ->keyBy('seat_id');

        return view('livewire.dashboard.vip-booking', [
            'event' => $event,
            'vipSeats' => $vipSeats,
            'bookings' => $bookings,
        ]);
    }
}
