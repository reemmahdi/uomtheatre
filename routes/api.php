<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SeatAvailabilityController;
use App\Http\Controllers\Api\SeatMapController;
use App\Http\Controllers\Api\SeatsApiController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UOMTheatre (مُحدّث - إصلاحات Claude)
|--------------------------------------------------------------------------
|
| ✨ التعديلات:
|   🔴 إزالة /seats/{eventId} المكرر (كان يلغي حماية الـ authenticated route)
|   🔴 إضافة rate limiting على /login و /register
|   🟡 نقل availability routes لمجموعة admin (تحتاج صلاحية manageVipSeats)
|   🟡 إضافة names لكل routes (للـ tinker + reverse routing)
|
*/

// ============================================
// روابط عامة (بدون تسجيل دخول)
// ============================================

// ✨ مُحسّن: rate limiting على auth endpoints (5 محاولات/دقيقة)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
});

// ✨ معلومات عامة عن الفعاليات (للجمهور قبل التسجيل في التطبيق)
Route::get('/events', [EventController::class, 'publicIndex'])->name('api.events.public');
Route::get('/events/{id}', [EventController::class, 'show'])->name('api.events.show');

// ❌ مُصحَّح: تم حذف الـ duplicate route التالي:
// Route::get('/seats/{eventId}', [SeatsApiController::class, 'show']);
// كان يجعل /api/seats/{eventId} متاحاً بدون authentication!
// النسخة الصحيحة موجودة داخل auth:sanctum أدناه.

// ============================================
// روابط تحتاج تسجيل دخول (Sanctum)
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    // ── المصادقة ──
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // ── المقاعد (للجمهور المسجّل، لرؤية المقاعد المتاحة لفعالية) ──
    Route::get('/seats/{eventId}', [SeatsApiController::class, 'show'])
        ->name('api.seats.show');

    Route::get('/events/{eventId}/seat-map', [SeatMapController::class, 'getSeatMap'])
        ->name('api.events.seat-map');

    // ── الحجوزات ──
    Route::get('/my-reservations', [ReservationController::class, 'myReservations'])
        ->name('api.reservations.mine');
    Route::post('/reservations', [ReservationController::class, 'store'])
        ->middleware('throttle:30,1')   // ✨ rate limit للحجز (30/دقيقة)
        ->name('api.reservations.store');
    Route::get('/reservations/{id}/ticket', [ReservationController::class, 'ticket'])
        ->name('api.reservations.ticket');
    Route::patch('/reservations/{id}/cancel', [ReservationController::class, 'cancel'])
        ->name('api.reservations.cancel');

    // ── الإشعارات ──
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');

    // ============================================
    // 🛡️ روابط الإدارة (admin middleware)
    // ============================================
    Route::middleware('admin')->prefix('admin')->group(function () {
        // ── المستخدمون ──
        Route::get('/users', [UserController::class, 'index'])->name('api.admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('api.admin.users.store');
        Route::get('/users/{id}', [UserController::class, 'show'])->name('api.admin.users.show');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('api.admin.users.update');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('api.admin.users.toggle');
        Route::get('/roles', [UserController::class, 'roles'])->name('api.admin.roles');

        // ── الفعاليات ──
        Route::get('/events', [EventController::class, 'index'])->name('api.admin.events.index');
        Route::post('/events', [EventController::class, 'store'])->name('api.admin.events.store');
        Route::put('/events/{id}', [EventController::class, 'update'])->name('api.admin.events.update');
        Route::patch('/events/{id}/status', [EventController::class, 'changeStatus'])
            ->name('api.admin.events.status');
        Route::post('/events/{id}/vip-seats', [EventController::class, 'reserveVip'])
            ->name('api.admin.events.vip');
        Route::get('/events/{id}/logs', [EventController::class, 'logs'])
            ->name('api.admin.events.logs');

        // ──────────────────────────────────────
        // ✨ مُحسّن: availability routes داخل admin
        // (تحتاج صلاحية manageVipSeats - الـ Controller يفحصها)
        // ──────────────────────────────────────
        Route::get('/events/{eventUuid}/availability', [SeatAvailabilityController::class, 'show'])
            ->name('api.events.availability.show');
        Route::post('/events/{eventUuid}/availability/save', [SeatAvailabilityController::class, 'save'])
            ->middleware('throttle:60,1')   // 60 saves/دقيقة كافية
            ->name('api.events.availability.save');

        // ── تسجيل الحضور ──
        Route::post('/check-in', [CheckInController::class, 'checkIn'])
            ->middleware('throttle:120,1')   // 120 scans/دقيقة (لـ موظف الاستقبال)
            ->name('api.admin.checkin');

        // ── لوحة التحكم ──
        Route::get('/events/{id}/dashboard', [DashboardController::class, 'eventDashboard'])
            ->name('api.admin.dashboard.event');
        Route::get('/dashboard', [DashboardController::class, 'overview'])
            ->name('api.admin.dashboard.overview');
    });
});
