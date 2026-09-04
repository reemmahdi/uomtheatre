<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use Livewire\Attributes\Locked;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Seat;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('حجز مقاعد الوفود')]
class VipBooking extends BaseComponent
{
    #[Locked]
    public int $eventId;

    #[Locked]
    public string $eventUuid = '';

    public string $guestName = '';
    public string $guestPhone = '';
    public int $selectedSeatId = 0;

    public ?int $editBookingId = null;
    public string $editGuestName = '';
    public string $editGuestPhone = '';

    public ?array $viewBooking = null;

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

    public function selectSeat(int $seatId): void
    {
        $this->authorizeManageVip();

        $event = Event::findOrFail($this->eventId);
        if ($event->is_booking_paused) {
            $this->swalError('الحجز موقوف مؤقتاً لهذه الفعالية. لا يمكن إضافة حجوزات جديدة.');
            return;
        }

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
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->status !== 'cancelled') {
                    $msg = $existing->type === 'vip_guest'
                        ? 'هذا المقعد محجوز للوفد ' . ($existing->guest_name ?? 'ضيف')
                        : 'هذا المقعد محجوز من قبل الجمهور';
                    throw new \RuntimeException($msg);
                }

                if ($existing) {
                    $existing->forceFill([
                        'user_id'     => Auth::id(),
                        'status'      => 'confirmed',
                        'type'        => 'vip_guest',
                        'guest_name'  => $this->guestName,
                        'guest_phone' => $this->guestPhone,
                        'qr_code'     => \App\Models\Reservation::generateQrCode(),
                    ])->save();

                    return $existing;
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
        } catch (\Illuminate\Database\QueryException $e) {
            $this->swalError('تعذر الحجز — يبدو أن المقعد حُجز للتو. حدّث الصفحة وحاول مجدداً');
        } catch (\Exception $e) {
            $this->swalError('فشل الحجز: ' . $e->getMessage());
        }
    }

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

    public function openEditBooking(int $reservationId): void
    {
        $this->authorizeManageVip();
        $res = $this->getReservationForThisEvent($reservationId);

        $this->editBookingId  = $res->id;
        $this->editGuestName  = $res->guest_name ?? '';
        $this->editGuestPhone = $res->guest_phone ?? '';

        $this->dispatch('open-modal', id: 'editBookingModal');
    }

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
                ->where('type', 'vip_guest')
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

    public function render()
    {
        $this->authorizeManageVip();

        $event = Event::with('status')->findOrFail($this->eventId);

        $vipFixedIds = Seat::where('is_vip_reserved', true)
            ->pluck('id')
            ->toArray();

        $excludedIds = \App\Models\EventSeatAvailability::where('event_id', $this->eventId)
            ->where('is_public_available', false)
            ->pluck('seat_id')
            ->toArray();

        $vipSeatIds = array_values(array_unique(array_merge($vipFixedIds, $excludedIds)));

        $allSeats = Seat::with('section')
            ->whereIn('id', $vipSeatIds)
            ->orderBy('section_id')
            ->orderBy('row_number')
            ->orderBy('seat_number')
            ->get();

        $allReservations = Reservation::where('event_id', $this->eventId)
            ->whereIn('seat_id', $vipSeatIds)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->keyBy('seat_id');

        $seatsBySection = $allSeats->groupBy('section.name');

        $stats = [
            'total_seats'     => $allSeats->count(),
            'vip_booked'      => $allReservations->where('type', 'vip_guest')->count(),
            'public_reserved' => 0,
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
