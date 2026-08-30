<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/invitation/{qrCode}', \App\Livewire\InvitationView::class)
    ->name('invitation.show');

Route::middleware('throttle:6,1')->group(function () {
    Route::get('/login', function () {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('pages.login');
    })->name('login');
});

Route::match(['get', 'post'], '/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح');
})->name('dashboard.logout');

Route::middleware('admin.web')->group(function () {
    Route::get('/dashboard', fn() => view('pages.dashboard'))->name('dashboard');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/dashboard/users', fn() => view('pages.users'))->name('dashboard.users');
        Route::get('/dashboard/staff', fn() => view('pages.staff'))->name('dashboard.staff');

        Route::get('/dashboard/permissions', fn() => view('pages.page_permissions'))
            ->name('dashboard.permissions');
    });

    Route::middleware('role:super_admin,theater_manager,event_manager')->group(function () {
        Route::get('/dashboard/events', fn() => view('pages.events'))->name('dashboard.events');
    });

    Route::middleware('role:super_admin,university_office')->group(function () {
        Route::get('/dashboard/my-approvals', fn() => view('pages.page_event-approvals'))
            ->name('dashboard.event-approvals');
    });

    $uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    Route::middleware('role:super_admin,event_manager')->group(function () use ($uuidPattern) {
        Route::get('/dashboard/vip-events', fn() => view('pages.vip-events'))
            ->name('dashboard.vip-events');

        Route::get('/dashboard/events/{eventUuid}/vip-booking',
            fn($eventUuid) => view('pages.page_vip-booking', ['eventUuid' => $eventUuid])
        )
        ->where('eventUuid', $uuidPattern)
        ->name('dashboard.vip-booking');

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

    Route::middleware('role:super_admin,receptionist')->group(function () {
        Route::get('/dashboard/check-in', fn() => view('pages.checkin'))->name('dashboard.checkin');
    });

    Route::middleware('role:super_admin,theater_manager,receptionist')->group(function () {
        Route::get('/dashboard/seats-display', fn() => view('pages.seats-display'))
            ->name('dashboard.seats-display');
    });

    Route::middleware('role:super_admin,university_office')->group(function () {
        Route::get('/dashboard/stats', fn() => view('pages.stats'))->name('dashboard.stats');
    });

    Route::get('/seats-map', fn() => view('seats-map'))->name('seats-map');

    Route::middleware('role:super_admin,event_manager')->group(function () use ($uuidPattern) {
        Route::get('/dashboard/events/{eventUuid}/vip-guests/print-list', function (string $eventUuid) {
            $event = \App\Models\Event::where('uuid', $eventUuid)->firstOrFail();
            if (!\Illuminate\Support\Facades\Auth::user()->can('manageVipSeats', $event)) abort(403);

            $bookings = \App\Models\Reservation::with(['seat.section'])
                ->where('event_id', $event->id)
                ->where('type', 'vip_guest')
                ->where('status', '!=', 'cancelled')
                ->orderBy('created_at', 'asc')
                ->get();

            return view('pages.vip-guests-print-list', compact('event', 'bookings'));
        })->where('eventUuid', $uuidPattern)->name('dashboard.vip-guests.print-list');

        Route::get('/dashboard/events/{eventUuid}/vip-guests/print-stickers', function (string $eventUuid) {
            $event = \App\Models\Event::where('uuid', $eventUuid)->firstOrFail();
            if (!\Illuminate\Support\Facades\Auth::user()->can('manageVipSeats', $event)) abort(403);

            $bookings = \App\Models\Reservation::with(['seat.section'])
                ->where('event_id', $event->id)
                ->where('type', 'vip_guest')
                ->where('status', '!=', 'cancelled')
                ->orderBy('seat_id', 'asc')
                ->get();

            return view('pages.vip-guests-print-stickers', compact('event', 'bookings'));
        })->where('eventUuid', $uuidPattern)->name('dashboard.vip-guests.print-stickers');
    });

    Route::middleware(['role:super_admin,event_manager', 'throttle:60,1'])->prefix('api/events')->group(function () use ($uuidPattern) {
    Route::get('/{eventUuid}/availability', function (string $eventUuid) {
            $event = \App\Models\Event::where('uuid', $eventUuid)->firstOrFail();

            if (!\Illuminate\Support\Facades\Auth::user()->can('manageVipSeats', $event)) {
                return response()->json(['error' => 'غير مصرح لك'], 403);
            }

            if (!in_array($event->status?->name, ['active', 'published'], true)) {
                return response()->json([
                    'error' => 'يمكن إدارة المقاعد فقط للفعاليات النشطة أو المنشورة'
                ], 400);
            }

            $service = app(\App\Services\EventSeatAvailabilityService::class);

            if (!$service->isInitialized($event)) {
                $service->initializeForEvent($event);
            }

            $excludedSeatIds = $service->getExcludedSeatIds($event);

            $excludedKeys = \App\Models\Seat::whereIn('id', $excludedSeatIds)
                ->pluck('label')
                ->toArray();

            // ✅ كل المقاعد الحقيقية من قاعدة البيانات (عشان يرسمها الجافاسكربت)
            $seats = \App\Models\Seat::with('section')
                ->orderBy('section_id')
                ->orderBy('row_number')
                ->orderBy('seat_number')
                ->get()
                ->map(fn($s) => [
                    'label'   => $s->label,
                    'section' => $s->section->name,
                    'row'     => (int) $s->row_number,
                    'num'     => (int) $s->seat_number,
                    'vip'     => (bool) $s->is_vip_reserved,
                ])
                ->values();

            // ضيوف الوفود المحجوزون (label => [الاسم، الجوال])
            $guests = \App\Models\Reservation::with('seat')
                ->where('event_id', $event->id)
                ->where('type', 'vip_guest')
                ->where('status', '!=', 'cancelled')
                ->get()
                ->reduce(function ($acc, $r) {
                    if ($r->seat && $r->seat->label) {
                        $acc[$r->seat->label] = [
                            'name'  => $r->guest_name,
                            'phone' => $r->guest_phone,
                        ];
                    }
                    return $acc;
                }, []);

            return response()->json([
                'excluded_seat_keys' => $excludedKeys,
                'seats'              => $seats,
                'guests'             => $guests,
                'stats'              => $service->getStats($event),
            ]);
        })->where('eventUuid', $uuidPattern);

        Route::post('/{eventUuid}/availability/save', function (string $eventUuid, \Illuminate\Http\Request $request) {
            $event = \App\Models\Event::where('uuid', $eventUuid)->firstOrFail();

            if (!\Illuminate\Support\Facades\Auth::user()->can('manageVipSeats', $event)) {
                return response()->json(['error' => 'غير مصرح لك'], 403);
            }

            $excludedKeys = $request->input('excluded_seat_keys', []);

            if (!is_array($excludedKeys)) {
                return response()->json(['error' => 'بيانات غير صحيحة'], 422);
            }

            $excludedSeatIds = \App\Models\Seat::whereIn('label', $excludedKeys)
                ->pluck('id')
                ->toArray();

            $allSeatIds = \App\Models\Seat::pluck('id')->toArray();
            $availableSeatIds = array_values(array_diff($allSeatIds, $excludedSeatIds));

            $service = app(\App\Services\EventSeatAvailabilityService::class);

            \Illuminate\Support\Facades\DB::transaction(function () use ($event, $availableSeatIds, $excludedSeatIds, $service) {
                if (!empty($availableSeatIds)) {
                    $service->bulkUpdate($event, $availableSeatIds, true);
                }
                if (!empty($excludedSeatIds)) {
                    $service->bulkUpdate($event, $excludedSeatIds, false, 'استبعاد من قبل مدير الإعلام');
                }
            });

            return response()->json([
                'success'        => true,
                'excluded_count' => count($excludedSeatIds),
                'available_count' => count($availableSeatIds),
                'stats'          => $service->getStats($event),
            ]);
        })->where('eventUuid', $uuidPattern);

        // حجز ضيف وفد على مقعد (أو إلغاؤه إذا الاسم فارغ) — مطابق لنظام VipBooking
        Route::post('/{eventUuid}/book-guest', function (string $eventUuid, \Illuminate\Http\Request $request) {
            $event = \App\Models\Event::where('uuid', $eventUuid)->firstOrFail();

            if (!\Illuminate\Support\Facades\Auth::user()->can('manageVipSeats', $event)) {
                return response()->json(['error' => 'غير مصرح لك'], 403);
            }

            $label      = $request->input('label');
            $guestName  = trim((string) $request->input('guest_name', ''));
            $guestPhone = trim((string) $request->input('guest_phone', ''));

            $seat = \App\Models\Seat::where('label', $label)->first();
            if (!$seat) {
                return response()->json(['error' => 'مقعد غير موجود'], 404);
            }

            // أي حجز نشط على هذا المقعد بهذه الفعالية
            $existing = \App\Models\Reservation::where('event_id', $event->id)
                ->where('seat_id', $seat->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            // اسم فارغ = إلغاء حجز الوفد
            if ($guestName === '') {
                if ($existing && $existing->type === 'vip_guest') {
                    $existing->cancel();
                }
                return response()->json(['success' => true, 'booked' => false, 'label' => $label]);
            }

            if ($existing) {
                // لا نسمح بالكتابة فوق حجز جمهور
                if ($existing->type !== 'vip_guest') {
                    return response()->json(['error' => 'هذا المقعد محجوز من قبل الجمهور'], 409);
                }
                $existing->update([
                    'guest_name'  => $guestName,
                    'guest_phone' => $guestPhone ?: null,
                ]);
            } else {
                \App\Models\Reservation::create([
                    'user_id'     => \Illuminate\Support\Facades\Auth::id(),
                    'event_id'    => $event->id,
                    'seat_id'     => $seat->id,
                    'status'      => 'confirmed',
                    'type'        => 'vip_guest',
                    'guest_name'  => $guestName,
                    'guest_phone' => $guestPhone ?: null,
                ]);
            }

            return response()->json([
                'success'     => true,
                'booked'      => true,
                'label'       => $label,
                'guest_name'  => $guestName,
                'guest_phone' => $guestPhone,
            ]);
        })->where('eventUuid', $uuidPattern);
    });
});

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
