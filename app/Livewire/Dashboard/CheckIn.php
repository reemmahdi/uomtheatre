<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Reservation;
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

    public function scan()
    {
        $this->validate(['qrCode'=>'required|string'],['qrCode.required'=>'أدخل رمز QR']);
        $res = Reservation::with(['user','event','seat.section'])->where('qr_code',$this->qrCode)->first();
        if (!$res) { $this->message='رمز QR غير صالح'; $this->messageType='danger'; $this->checkInData=[]; return; }
        if ($res->status==='cancelled') { $this->message='هذا الحجز ملغي'; $this->messageType='danger'; $this->checkInData=[]; return; }
        if ($res->status==='checked_in') { $this->message='تم تسجيل الحضور مسبقاً'; $this->messageType='warning'; $this->checkInData=[]; return; }
        $res->checkIn();
        $this->message='تم تسجيل الحضور بنجاح ✅'; $this->messageType='success';
        $this->checkInData=['name'=>$res->user->name ?? $res->guest_name ?? 'ضيف','event'=>$res->event->title,'section'=>$res->seat->section->name,'seat'=>$res->seat->label,'type'=>$res->type==='vip_guest'?'وفود':'عادي'];
        $this->qrCode='';
    }

    public function render()
    {
        if (!in_array(Auth::user()->role->name, ['super_admin','receptionist'])) return redirect()->route('dashboard');
        return view('livewire.dashboard.checkin');
    }
}
