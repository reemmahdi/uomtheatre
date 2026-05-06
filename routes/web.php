<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// ═══════════════════════════════════════════════════════════
// ✨ Routes العامة (بدون تسجيل دخول)
// ═══════════════════════════════════════════════════════════

// صفحة الدعوة الإلكترونية للوفود (Livewire Full-Page Component)
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

    // ──────────────────────────────────────────────────────
    // ✨ 🛡️ UUID-based Event Routes (حماية ضد IDOR)
    // ──────────────────────────────────────────────────────
    // الـ {eventUuid} يستقبل UUID بدل ID رقمي
    // مثال: /dashboard/events/9f1d2c4a-3b5e-4f78-9c12-abcd1234ef56/vip-booking
    // ──────────────────────────────────────────────────────

    // ── حجز مقاعد الوفود: مدير النظام + مدير الإعلام فقط ──
    Route::middleware('role:super_admin,event_manager')->group(function () {
        Route::get('/dashboard/vip-events', fn() => view('pages.vip-events'))
            ->name('dashboard.vip-events');

        // ✨ UUID بدل ID رقمي - نمرر eventId و eventUuid معاً للتوافق مع الـ View
        Route::get('/dashboard/events/{eventUuid}/vip-booking',
            fn($eventUuid) => view('pages.vip-booking', [
                'eventUuid' => $eventUuid,
                'eventId' => $eventUuid, // ✅ للتوافق مع الـ Blade view
            ])
        )
        ->where('eventUuid', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}')
        ->name('dashboard.vip-booking');

        // ✨ UUID بدل ID رقمي - نمرر eventId و eventUuid معاً للتوافق مع الـ View
        Route::get('/dashboard/events/{eventUuid}/cancellation-notices',
            fn($eventUuid) => view('pages.event-cancellation-notices', [
                'eventUuid' => $eventUuid,
                'eventId' => $eventUuid, // ✅ للتوافق مع الـ Blade view
            ])
        )
        ->where('eventUuid', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}')
        ->name('dashboard.event-cancellation-notices');
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
