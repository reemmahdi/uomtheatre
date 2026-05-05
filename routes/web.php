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
// 🛡️ لوحة التحكم - حماية بطبقتين:
//    1. admin.web : التحقق من تسجيل الدخول + التفعيل + ليس role=user
//    2. role:xxx  : التحقق من الدور المحدد لكل صفحة
// ═══════════════════════════════════════════════════════════

Route::middleware('admin.web')->group(function () {

    // ── الصفحة الرئيسية: متاحة لكل الموظفين ──
    Route::get('/dashboard', fn() => view('pages.dashboard'))->name('dashboard');

    // ── إدارة المستخدمين والموظفين: مدير النظام فقط ──
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/dashboard/users', fn() => view('pages.users'))->name('dashboard.users');
        Route::get('/dashboard/staff', fn() => view('pages.staff'))->name('dashboard.staff');
    });

    // ── الفعاليات: مدير النظام + مدير المسرح + مدير الإعلام ──
    Route::middleware('role:super_admin,theater_manager,event_manager')->group(function () {
        Route::get('/dashboard/events', fn() => view('pages.events'))->name('dashboard.events');
    });

    // ── حجز مقاعد الوفود: مدير النظام + مدير الإعلام فقط ──
    Route::middleware('role:super_admin,event_manager')->group(function () {
        Route::get('/dashboard/vip-events', fn() => view('pages.vip-events'))
            ->name('dashboard.vip-events');

        Route::get('/dashboard/events/{eventId}/vip-booking',
            fn($eventId) => view('pages.vip-booking', ['eventId' => $eventId])
        )->name('dashboard.vip-booking');

        Route::get('/dashboard/events/{eventId}/cancellation-notices',
            fn($eventId) => view('pages.event-cancellation-notices', ['eventId' => $eventId])
        )->name('dashboard.event-cancellation-notices');
    });

    // ── تسجيل الحضور: مدير النظام + موظف الاستقبال فقط ──
    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::get('/dashboard/check-in', fn() => view('pages.checkin'))->name('dashboard.checkin');
    });

    // ── شاشة العرض المباشر: مدير النظام + مدير المسرح + موظف الاستقبال ──
    Route::middleware('role:super_admin,theater_manager,receptionist')->group(function () {
        Route::get('/dashboard/seats-display', fn() => view('pages.seats-display'))
            ->name('dashboard.seats-display');
    });

    // ── الإحصائيات: مدير النظام + مكتب رئيس الجامعة فقط ──
    Route::middleware('role:super_admin,university_office')->group(function () {
        Route::get('/dashboard/stats', fn() => view('pages.stats'))->name('dashboard.stats');
    });
});

Route::get('/', fn() => redirect('/login'));

// خارطة المقاعد - للعرض
Route::get('/seats-map', function () {
    return view('seats-map');
})->name('seats-map');
