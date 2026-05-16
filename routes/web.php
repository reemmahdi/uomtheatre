<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — UOMTheatre (تصميم جديد - موافق واحد)
|--------------------------------------------------------------------------
|
| ✨ التعديلات في هذه النسخة:
|   - حذف theater_manager من /dashboard/my-approvals
|     (مدير المسرح صار "مشاهد فقط" بدون موافقة)
|   - مدير المسرح يحتفظ بـ /dashboard/events للمتابعة (read-only)
|     لكن منطق read-only يُطبَّق داخل Livewire component
|
*/

// ═══════════════════════════════════════════════════════════
// ✨ Routes العامة (بدون تسجيل دخول)
// ═══════════════════════════════════════════════════════════

// صفحة الدعوة الإلكترونية للوفود (آمنة - qr_code random + cancellation check)
Route::get('/invitation/{qrCode}', \App\Livewire\InvitationView::class)
    ->name('invitation.show');

// ═══════════════════════════════════════════════════════════
// المصادقة
// ═══════════════════════════════════════════════════════════

// rate limiting على login (6 محاولات/دقيقة لكل IP)
Route::middleware('throttle:6,1')->group(function () {
    Route::get('/login', function () {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('pages.login');
    })->name('login');
});

// ✨ مُحدَّث: قبول GET و POST لتجنب 403/419 عند انتهاء CSRF token
//    GET آمن هنا لأن أسوأ سيناريو هو تسجيل خروج المستخدم (مزعج لكن غير ضار)
Route::match(['get', 'post'], '/logout', function () {
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

        // شاشة إدارة الصلاحيات (للسوبر أدمن فقط)
        Route::get('/dashboard/permissions', fn() => view('pages.page_permissions'))
            ->name('dashboard.permissions');
    });

    // ── الفعاليات: مدير النظام + مدير المسرح (للمتابعة) + مدير الإعلام ──
    // 💡 مدير المسرح يدخل لكن يشاهد فقط (read-only) - المنطق في Livewire
    Route::middleware('role:super_admin,theater_manager,event_manager')->group(function () {
        Route::get('/dashboard/events', fn() => view('pages.events'))->name('dashboard.events');
    });

    // ── شاشة الموافقات: لمكتب الرئاسة + super_admin فقط ──
    // ✨ مُحدَّث: حُذف theater_manager (ما عاد يوافق)
    Route::middleware('role:super_admin,university_office')->group(function () {
        Route::get('/dashboard/my-approvals', fn() => view('pages.page_event-approvals'))
            ->name('dashboard.event-approvals');
    });

    // ──────────────────────────────────────────────────────
    // 🛡️ UUID-based Event Routes (حماية ضد IDOR)
    // ──────────────────────────────────────────────────────

    $uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

    // ── حجز مقاعد الوفود: مدير النظام + مدير الإعلام فقط ──
    Route::middleware('role:super_admin,event_manager')->group(function () use ($uuidPattern) {
        Route::get('/dashboard/vip-events', fn() => view('pages.vip-events'))
            ->name('dashboard.vip-events');

        Route::get('/dashboard/events/{eventUuid}/vip-booking',
            fn($eventUuid) => view('pages.page_vip-booking', ['eventUuid' => $eventUuid])
        )
        ->where('eventUuid', $uuidPattern)
        ->name('dashboard.vip-booking');

        // شاشة تحديد المقاعد المتاحة للجمهور
        Route::get('/dashboard/events/{eventUuid}/seat-availability',
            fn($eventUuid) => view('pages.page_seat-availability', ['eventUuid' => $eventUuid])
        )
        ->where('eventUuid', $uuidPattern)
        ->name('dashboard.seat-availability');

        Route::get('/dashboard/events/{eventUuid}/cancellation-notices',
            fn($eventUuid) => view('pages.page_event-cancellation-notices', ['eventUuid' => $eventUuid])
        )
        ->where('eventUuid', $uuidPattern)
        ->name('dashboard.event-cancellation-notices');

        Route::get('/dashboard/events/{eventUuid}/vip-guests',
            fn($eventUuid) => view('pages.page_vip-guests', ['eventUuid' => $eventUuid])
        )
        ->where('eventUuid', $uuidPattern)
        ->name('dashboard.vip-guests');
    });

    // ── تسجيل الحضور: مدير النظام + موظف الاستقبال فقط ──
    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::get('/dashboard/check-in', fn() => view('pages.checkin'))->name('dashboard.checkin');
    });

    // ── شاشة العرض المباشر ──
    Route::middleware('role:super_admin,theater_manager,receptionist')->group(function () {
        Route::get('/dashboard/seats-display', fn() => view('pages.seats-display'))
            ->name('dashboard.seats-display');
    });

    // ── الإحصائيات: مدير النظام + مكتب رئيس الجامعة فقط ──
    Route::middleware('role:super_admin,university_office')->group(function () {
        Route::get('/dashboard/stats', fn() => view('pages.stats'))->name('dashboard.stats');
    });

    // ✨ /seats-map داخل admin.web (تجنب data leak)
    Route::get('/seats-map', fn() => view('seats-map'))->name('seats-map');
});

// redirect ذكي حسب حالة المصادقة
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
