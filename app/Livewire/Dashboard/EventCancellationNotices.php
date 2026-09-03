<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('إشعارات الإلغاء')]
class EventCancellationNotices extends BaseComponent
{
    protected array $allowedRoles = ['super_admin', 'event_manager'];

    public int $eventId;
    public string $eventUuid = '';

    public function mount(string $eventUuid)
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $eventUuid)) {
            abort(404, 'معرّف الفعالية غير صحيح');
        }

        $event = Event::where('uuid', $eventUuid)->firstOrFail();

        $this->eventUuid = $eventUuid;
        $this->eventId = $event->id;
    }

    public function getCancellationWhatsAppLink(int $reservationId): string
    {
        $res = Reservation::with(['event', 'seat.section'])
            ->where('event_id', $this->eventId)
            ->findOrFail($reservationId);
        $event = $res->event;

        $msg  = "*جامعة الموصل - مسرح الجامعة*\n";
        $msg .= "─────────────────────────\n\n";

        $msg .= "السلام عليكم ورحمة الله وبركاته\n\n";
        $msg .= "الأستاذ/ة الفاضل/ة: *{$res->guest_name}*\n\n";

        $msg .= "تحية طيبة وبعد،\n\n";

        $msg .= "نأسف لإبلاغكم بإلغاء الفعالية الموسومة بـ:\n";
        $msg .= "*{$event->title}*\n\n";

        $msg .= "والتي كان من المقرر إقامتها بتاريخ ";
        $msg .= $event->start_datetime->format('Y-m-d');
        $msg .= " في تمام الساعة ";
        $msg .= $event->start_datetime->format('H:i');
        $msg .= ".\n\n";

        if (!empty($event->cancellation_reason)) {
            $msg .= "*سبب الإلغاء:*\n";
            $msg .= "{$event->cancellation_reason}\n\n";
        }

        $msg .= "نعتذر عن أي إزعاج قد يسببه ذلك، ونشكر لكم تفهمكم وحسن تعاونكم.\n\n";

        $msg .= "تفضلوا بقبول فائق الاحترام والتقدير،،،\n\n";
        $msg .= "*إدارة مسرح جامعة الموصل*";

        $phone = preg_replace('/[^0-9]/', '', $res->guest_phone);
        if (str_starts_with($phone, '0')) {
            $phone = '964' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone . '?text=' . urlencode($msg);
    }

    public function render()
    {
        $event = Event::with('status')->findOrFail($this->eventId);

        if ($event->status->name !== 'cancelled') {
            session()->flash('error', 'هذه الفعالية ليست ملغاة');
            return redirect()->route('dashboard.events');
        }

        $vipBookings = Reservation::with(['seat.section'])
            ->where('event_id', $this->eventId)
            ->where('type', 'vip_guest')
            ->where('status', '!=', 'cancelled')
            ->get();

        return view('livewire.dashboard.event-cancellation-notices', [
            'event'       => $event,
            'vipBookings' => $vipBookings,
        ]);
    }
}
