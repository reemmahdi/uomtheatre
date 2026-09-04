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
use App\Http\Controllers\Api\GoogleAuthController;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/google', [GoogleAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/events', [EventController::class, 'publicIndex'])->name('api.events.public');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('api.events.show');
    Route::get('/seats/{eventId}', [SeatsApiController::class, 'show'])->name('api.seats.show');
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/auth/profile', [GoogleAuthController::class, 'completeProfile']);
    Route::post('/reservations/{id}/confirm-change', [ReservationController::class, 'confirmChange']);
    Route::post('/reservations/{id}/reject-change', [ReservationController::class, 'rejectChange']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/device-token', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'device_name' => 'nullable|string|max:120',
            'platform' => 'nullable|string|in:android,ios',
            'app_version' => 'nullable|string|max:30',
        ]);
        $user = $request->user();
        $owner = \App\Models\DeviceToken::where('token', $data['token'])->value('user_id');
        if ($owner !== null && $owner !== $user->id) {
            return response()->json(['message' => 'هذا الجهاز مسجل لحساب آخر'], 409);
        }
        \App\Models\DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );
        $user->forceFill(['fcm_token' => $data['token']])->save();
        return response()->json(['message' => 'ok']);
    })->middleware('throttle:10,1');
    Route::get('/events/{eventId}/seat-map', [SeatMapController::class, 'getSeatMap'])
        ->name('api.events.seat-map');

    Route::get('/my-reservations', [ReservationController::class, 'myReservations'])
        ->name('api.reservations.mine');
    Route::post('/reservations', [ReservationController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.reservations.store');
    Route::get('/reservations/{id}/ticket', [ReservationController::class, 'ticket'])
        ->name('api.reservations.ticket');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');

    Route::middleware(['admin', 'abilities:staff'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('api.admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('api.admin.users.store');
        Route::get('/users/{id}', [UserController::class, 'show'])->name('api.admin.users.show');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('api.admin.users.update');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('api.admin.users.toggle');
        Route::get('/roles', [UserController::class, 'roles'])->name('api.admin.roles');

        Route::get('/events', [EventController::class, 'index'])->name('api.admin.events.index');
        Route::post('/events', [EventController::class, 'store'])->name('api.admin.events.store');
        Route::put('/events/{id}', [EventController::class, 'update'])->name('api.admin.events.update');
        Route::patch('/events/{id}/status', [EventController::class, 'changeStatus'])
            ->name('api.admin.events.status');
        Route::post('/events/{id}/vip-seats', [EventController::class, 'reserveVip'])
            ->name('api.admin.events.vip');
        Route::get('/events/{id}/logs', [EventController::class, 'logs'])
            ->name('api.admin.events.logs');

        Route::get('/events/{eventUuid}/availability', [SeatAvailabilityController::class, 'show'])
            ->name('api.events.availability.show');
        Route::post('/events/{eventUuid}/availability/save', [SeatAvailabilityController::class, 'save'])
            ->middleware('throttle:60,1')
            ->name('api.events.availability.save');

        Route::post('/check-in', [CheckInController::class, 'checkIn'])
            ->middleware('throttle:120,1')
            ->name('api.admin.checkin');

        Route::get('/events/{id}/dashboard', [DashboardController::class, 'eventDashboard'])
            ->name('api.admin.dashboard.event');
        Route::get('/dashboard', [DashboardController::class, 'overview'])
            ->name('api.admin.dashboard.overview');
    });
});
