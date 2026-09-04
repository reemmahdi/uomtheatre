<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('تسجيل الحضور')]
class CheckIn extends BaseComponent
{
    public string $qrCode = '';
    public string $message = '';
    public string $messageType = '';
    public array $checkInData = [];

    public string $filterEventId = '';
    public string $searchScans = '';

    protected function authorizeScan(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role?->name, [Role::SUPER_ADMIN, Role::RECEPTIONIST], true)) {
            abort(403, 'هذه الصفحة متاحة لموظف الاستقبال فقط');
        }
    }

    public function mount(): void
    {
        $this->authorizeScan();
    }

    public function scan(): void
    {
        $this->authorizeScan();

        $this->validate(
            ['qrCode' => 'required|string'],
            ['qrCode.required' => 'أدخل رمز QR']
        );

        $res = Reservation::with(['user', 'event', 'seat.section'])
            ->where('qr_code', $this->qrCode)
            ->first();


        if (!$res) {
            $this->message = 'رمز QR غير صالح';
            $this->messageType = 'danger';
            $this->checkInData = [];
            return;
        }

        if ($res->status === 'cancelled') {
            $this->message = 'هذا الحجز ملغي';
            $this->messageType = 'danger';
            $this->checkInData = [];
            return;
        }

        if ($res->status === 'checked_in') {
            $this->message = 'تم تسجيل الحضور مسبقاً';
            $this->messageType = 'warning';
            $this->checkInData = [];
            return;
        }

        $start = $res->event?->start_datetime;
        if ($start && now()->lt($start->copy()->subHours(2))) {
            $this->message = 'الفحص يفتح قبل ساعتين من موعد الفعالية — يبدأ '
                . $start->copy()->subHours(2)->format('Y-m-d h:i A');
            $this->messageType = 'warning';
            $this->checkInData = [];
            return;
        }

        if (!Auth::user()->can('checkIn', $res)) {
            $this->message = 'غير مصرح لك بتسجيل هذا الحضور';
            $this->messageType = 'danger';
            return;
        }

        if (!$res->checkIn(Auth::id())) {
            $this->message = 'تعذر تسجيل الحضور — الحجز ليس بحالة مؤكدة';
            $this->messageType = 'warning';
            $this->checkInData = [];
            return;
        }
        $this->message = 'تم تسجيل الحضور بنجاح';
        $this->messageType = 'success';

        $this->checkInData = [
            'name'    => ($res->type === 'vip_guest' ? ($res->guest_name ?? 'وفد') : ($res->user?->name ?? '—')) ?? $res->guest_name ?? 'ضيف',
            'event'   => $res->event?->title ?? '—',
            'section' => $res->seat?->section?->name ?? '—',
            'seat'    => $res->seat?->label ?? '—',
            'type'    => $res->type === 'vip_guest' ? 'وفود' : 'عادي',
        ];

        $this->qrCode = '';
    }

    public function render()
    {
        $this->authorizeScan();

        $events = Event::orderByDesc('start_datetime')
            ->limit(30)
            ->get(['id', 'title', 'start_datetime']);

        $scansQuery = Reservation::with(['user', 'event', 'seat.section'])
            ->where('status', 'checked_in');

        $attIn = null;
        $attTotal = null;
        if ($this->filterEventId !== '') {
            $scansQuery->where('event_id', (int) $this->filterEventId);

            $attTotal = Reservation::where('event_id', (int) $this->filterEventId)
                ->where('status', '!=', 'cancelled')
                ->count();
            $attIn = Reservation::where('event_id', (int) $this->filterEventId)
                ->where('status', 'checked_in')
                ->count();
        }

        $recentScans = $scansQuery->latest('updated_at')->limit(100)->get();

        if ($this->searchScans !== '') {
            $q = mb_strtolower($this->searchScans);
            $recentScans = $recentScans->filter(function ($r) use ($q) {
                $hay = mb_strtolower(
                    (($r->type === 'vip_guest' ? ($r->guest_name ?? 'وفد') : ($r->user?->name ?? '—')) ?? '') . ' '
                    . ($r->guest_name ?? '') . ' '
                    . ($r->seat?->label ?? '') . ' '
                    . ($r->qr_code ?? '')
                );
                return str_contains($hay, $q);
            })->values();
        }

        return view('livewire.dashboard.checkin', [
            'recentScans' => $recentScans,
            'events'      => $events,
            'attIn'       => $attIn,
            'attTotal'    => $attTotal,
        ]);
    }
}
