<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Seat;
use App\Services\EventSeatAvailabilityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * SeatAvailability — UOMTheatre
 * ════════════════════════════════════════════════════════════════
 *
 * شاشة تحديد المقاعد المتاحة للجمهور.
 *
 * المنطق:
 *   - تعرض خريطة المسرح كاملة (945 مقعد + 52 VIP)
 *   - مدير الإعلام يضغط مقعداً → يستبعده/يعيد إتاحته
 *   - مقاعد VIP محمية (لا يمكن تعديلها هنا)
 *   - زر حفظ يُرسل التغييرات لـ DB
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('تحديد المقاعد المتاحة')]
class SeatAvailability extends BaseComponent
{
    public Event $event;
    public string $eventUuid;

    public function mount(string $eventUuid)
    {
        $this->eventUuid = $eventUuid;
        $this->event = Event::where('uuid', $eventUuid)->firstOrFail();

        // التحقق من الصلاحيات
        if (!Auth::user()->can('manageVipSeats', $this->event)) {
            abort(403, 'غير مصرح لك');
        }

        if (!in_array($this->event->status->name, ['active', 'published'])) {
            abort(403, 'يمكن تحديد المقاعد فقط للفعاليات النشطة');
        }

        // التهيئة لو لم تكن مهيأة
        $service = app(EventSeatAvailabilityService::class);
        if (!$service->isInitialized($this->event)) {
            $service->initializeForEvent($this->event);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.seat-availability', [
            'event' => $this->event,
        ]);
    }
}
