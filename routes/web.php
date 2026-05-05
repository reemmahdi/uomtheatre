<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// ═══════════════════════════════════════════════════════════
// ✨ Routes العامة (بدون تسجيل دخول)
// ═══════════════════════════════════════════════════════════

// صفحة الدعوة الإلكترونية للوفود (Livewire Full-Page Component)
// الـ Layout محدّد في #[Layout('layouts.invitation')] داخل InvitationView.php
Route::get('/invitation/{qrCode}', \App\Livewire\InvitationView::class)
    ->name('invitation.show');

// ═══════════════════════════════════════════════════════════
// المصادقة
// ═══════════════════════════════════════════════════════════

Route::get('/login', function () {
    if (Auth::check()) return redirect()->route('dashboard');
    return view('pages.login');
})->name('login');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح');
})->name('dashboard.logout');

// ═══════════════════════════════════════════════════════════
// لوحة التحكم (تتطلب تسجيل دخول)
// ═══════════════════════════════════════════════════════════

Route::middleware('admin.web')->group(function () {
    Route::get('/dashboard',            fn() => view('pages.dashboard'))->name('dashboard');
    Route::get('/dashboard/users',      fn() => view('pages.users'))->name('dashboard.users');
    Route::get('/dashboard/staff',      fn() => view('pages.staff'))->name('dashboard.staff');
    Route::get('/dashboard/events',     fn() => view('pages.events'))->name('dashboard.events');
    Route::get('/dashboard/vip-events', fn() => view('pages.vip-events'))->name('dashboard.vip-events');
    Route::get('/dashboard/check-in',   fn() => view('pages.checkin'))->name('dashboard.checkin');
    Route::get('/dashboard/stats',      fn() => view('pages.stats'))->name('dashboard.stats');

    Route::get('/dashboard/events/{eventId}/vip-booking',
        fn($eventId) => view('pages.vip-booking', ['eventId' => $eventId])
    )->name('dashboard.vip-booking');

    Route::get('/dashboard/events/{eventId}/cancellation-notices',
        fn($eventId) => view('pages.event-cancellation-notices', ['eventId' => $eventId])
    )->name('dashboard.event-cancellation-notices');

    // ✨ جديد: شاشة عرض المقاعد التفاعلية
    Route::get('/dashboard/seats-display',
        fn() => view('pages.seats-display')
    )->name('dashboard.seats-display');
});

Route::get('/', fn() => redirect('/login'));


// خارطة المقاعد - للعرض
Route::get('/seats-map', function () {
    return view('seats-map');
})->name('seats-map');
