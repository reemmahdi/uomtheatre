<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Reservation;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * CheckIn — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 authorize() قبل scan
 *   🔴 redirect في mount بدل render
 *   🟡 nullsafe + constants
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('تسجيل الحضور')]
class CheckIn extends BaseComponent
{
    public string $qrCode = '';
    public string $message = '';
    public string $messageType = '';
    public array $checkInData = [];

    /**
     * ✨ helper: التحقق من صلاحية scan
     */
    protected function authorizeScan(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role?->name, [Role::SUPER_ADMIN, Role::RECEPTIONIST], true)) {
            abort(403, 'هذه الصفحة متاحة لموظف الاستقبال فقط');
        }
    }

    /**
     * ✨ مُحسّن: redirect في mount بدل render
     */
    public function mount(): void
    {
        $this->authorizeScan();
    }

    public function scan(): void
    {
        $this->authorizeScan();   // ✨ authorize check (مهم - methods حساسة)

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

        // ✨ استخدام Policy (موجودة في ReservationPolicy::checkIn)
        if (!Auth::user()->can('checkIn', $res)) {
            $this->message = 'غير مصرح لك بتسجيل هذا الحضور';
            $this->messageType = 'danger';
            return;
        }

        $res->checkIn();
        $this->message = 'تم تسجيل الحضور بنجاح ✅';
        $this->messageType = 'success';

        // ✨ nullsafe على relationships
        $this->checkInData = [
            'name'    => $res->user?->name ?? $res->guest_name ?? 'ضيف',
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
        return view('livewire.dashboard.checkin');
    }
}
