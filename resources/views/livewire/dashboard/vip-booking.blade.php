<div>

{{-- ═══════════════════════════════════════════════════════════════
     عنوان الفعالية + Banner التحذير
     ═══════════════════════════════════════════════════════════════ --}}

<div class="card-custom p-4 mb-4 event-title-card">
    <div class="d-flex align-items-center gap-3">
        <div class="event-icon-circle">
            <i class="bi bi-calendar-event"></i>
        </div>
        <div class="flex-grow-1">
            <div class="small text-muted mb-1">إدارة مقاعد الوفود</div>
            <h3 class="mb-0 event-main-title">{{ $event->title }}</h3>
        </div>
    </div>
</div>

@if($event->is_booking_paused)
<div class="card-custom p-4 mb-4 booking-paused-banner">
    <div class="d-flex align-items-center gap-3">
        <div class="paused-icon">
            <i class="bi bi-pause-circle-fill"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="mb-1" style="color: #92400E; font-weight: 700;">
                <i class="bi bi-exclamation-triangle-fill"></i> الحجز موقوف مؤقتاً
            </h5>
            <p class="mb-0" style="color: #78350F;">
                لا يمكن إضافة حجوزات جديدة لهذه الفعالية حالياً.
                <strong>الحجوزات السابقة محفوظة وسليمة.</strong>
            </p>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     إحصائيات + شرح الألوان
     ═══════════════════════════════════════════════════════════════ --}}

<div class="card-custom p-4 mb-4">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-box stat-box-total">
                <div class="stat-number">{{ $stats['total_seats'] }}</div>
                <div class="stat-label">إجمالي المقاعد</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box stat-box-vip">
                <div class="stat-number">{{ $stats['vip_booked'] }}</div>
                <div class="stat-label">وفود محجوزة</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box stat-box-public">
                <div class="stat-number">{{ $stats['public_reserved'] }}</div>
                <div class="stat-label">حجوزات الجمهور</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box stat-box-available">
                <div class="stat-number">{{ $stats['available'] }}</div>
                <div class="stat-label">متاح</div>
            </div>
        </div>
    </div>

    <div class="legend-row">
        <div class="legend-item"><span class="legend-color legend-vip"></span> <strong>وفد محجوز</strong> — اضغط لعرض/تعديل/إلغاء</div>
        <div class="legend-item"><span class="legend-color legend-public"></span> <strong>محجوز من الجمهور</strong> — لا يمكن تعديله</div>
        <div class="legend-item"><span class="legend-color legend-available"></span> <strong>متاح</strong> — اضغط للحجز كوفد</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     خريطة المقاعد - مقسمة حسب القسم
     ═══════════════════════════════════════════════════════════════ --}}

@foreach($seatsBySection as $sectionName => $sectionSeats)
@php
    $rowsInSection = $sectionSeats->groupBy('row_number')->sortKeys();
    $sectionVipCount = 0;
    foreach ($sectionSeats as $s) {
        if (isset($allReservations[$s->id]) && $allReservations[$s->id]->type === 'vip_guest') {
            $sectionVipCount++;
        }
    }
@endphp

<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0">
            <span class="section-badge">القسم {{ $sectionName }}</span>
            <span class="text-muted small ms-2">— {{ $sectionSeats->count() }} مقعد</span>
            @if($sectionVipCount > 0)
            <span class="badge ms-2" style="background: #C9A445; color: #fff;">
                <i class="bi bi-star-fill"></i> {{ $sectionVipCount }} وفد
            </span>
            @endif
        </h6>
        <span class="small text-muted">
            <i class="bi bi-arrow-left-right"></i> اسحب للتمرير
        </span>
    </div>

    @foreach($rowsInSection as $rowNumber => $rowSeats)
    <div class="seat-row-container">
        <div class="row-label">صف {{ $rowNumber }}</div>
        <div class="seats-scroll-row">
            @foreach($rowSeats->sortBy('seat_number') as $seat)
            @php
                $reservation = $allReservations[$seat->id] ?? null;
                $isVip = $reservation && $reservation->type === 'vip_guest';
                $isPublic = $reservation && $reservation->type !== 'vip_guest';
                $isAvailable = !$reservation;
            @endphp

            @if($isVip)
                {{-- 🟡 مقعد محجوز كوفد - قابل للضغط --}}
                <div class="seat-item-wrapper booked-wrapper"
                     wire:click="openViewBooking({{ $reservation->id }})"
                     title="{{ $seat->label }} - {{ $reservation->guest_name }}">
                    <div class="seat-item booked-vip">
                        <div class="seat-chair">
                            <div class="chair-back"></div>
                            <div class="chair-cushion"></div>
                        </div>
                    </div>
                    <div class="seat-number">{{ $seat->seat_number }}</div>
                    <div class="seat-name-tooltip">{{ $reservation->guest_name }}</div>
                </div>

            @elseif($isPublic)
                {{-- 🔴 محجوز من الجمهور - غير قابل للتعديل --}}
                <div class="seat-item-wrapper" title="{{ $seat->label }} - محجوز من الجمهور">
                    <div class="seat-item booked-public">
                        <div class="seat-chair">
                            <div class="chair-back"></div>
                            <div class="chair-cushion"></div>
                        </div>
                    </div>
                    <div class="seat-number">{{ $seat->seat_number }}</div>
                </div>

            @else
                {{-- ⚪ متاح --}}
                @if($event->is_booking_paused)
                    <div class="seat-item-wrapper" title="الحجز موقوف">
                        <div class="seat-item paused">
                            <div class="seat-chair">
                                <div class="chair-back"></div>
                                <div class="chair-cushion"></div>
                            </div>
                        </div>
                        <div class="seat-number">{{ $seat->seat_number }}</div>
                    </div>
                @else
                    <div class="seat-item-wrapper available-wrapper"
                         wire:click="selectSeat({{ $seat->id }})"
                         data-bs-toggle="modal"
                         data-bs-target="#bookSeatModal"
                         title="اضغط لحجز {{ $seat->label }} كوفد">
                        <div class="seat-item available">
                            <div class="seat-chair">
                                <div class="chair-back"></div>
                                <div class="chair-cushion"></div>
                            </div>
                        </div>
                        <div class="seat-number">{{ $seat->seat_number }}</div>
                    </div>
                @endif
            @endif
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endforeach

{{-- ═══════════════════════════════════════════════════════════════
     Modal: حجز مقعد جديد كوفد
     ═══════════════════════════════════════════════════════════════ --}}

<div wire:ignore.self class="modal fade" id="bookSeatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-star-fill text-warning"></i>
                    حجز مقعد كوفد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="bookSeat">
                <div class="modal-body">
                    @if($selectedSeatId)
                    @php $selSeat = \App\Models\Seat::with('section')->find($selectedSeatId); @endphp
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        المقعد المختار: <strong>{{ $selSeat?->label }}</strong>
                        @if($selSeat?->section)
                        (القسم {{ $selSeat->section->name }} - صف {{ $selSeat->row_number }})
                        @endif
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">اسم الضيف <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('guestName') is-invalid @enderror"
                               wire:model="guestName"
                               placeholder="مثال: د. أحمد محمد">
                        @error('guestName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('guestPhone') is-invalid @enderror"
                               wire:model="guestPhone"
                               placeholder="07XXXXXXXXX"
                               dir="ltr">
                        @error('guestPhone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">سيُستخدم لإرسال الدعوة عبر واتساب</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> حجز
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Modal: عرض تفاصيل وفد
     ═══════════════════════════════════════════════════════════════ --}}

<div wire:ignore.self class="modal fade" id="viewBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge"></i> تفاصيل الوفد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            @if($viewBooking)
            <div class="modal-body">
                <div class="info-row">
                    <span class="info-label">الاسم:</span>
                    <span class="info-value"><strong>{{ $viewBooking['guest_name'] }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">الجوال:</span>
                    <span class="info-value" dir="ltr">{{ $viewBooking['guest_phone'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">المقعد:</span>
                    <span class="info-value">
                        {{ $viewBooking['seat_label'] }}
                        <small class="text-muted">(القسم {{ $viewBooking['section_name'] }} - صف {{ $viewBooking['row_number'] }} - رقم {{ $viewBooking['seat_number'] }})</small>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الحجز:</span>
                    <span class="info-value">{{ $viewBooking['created_at'] }}</span>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ $this->getWhatsAppLink($viewBooking['id']) }}"
                   target="_blank" class="btn btn-success">
                    <i class="bi bi-whatsapp"></i> إرسال الدعوة
                </a>
                <button type="button" class="btn btn-warning"
                        data-bs-dismiss="modal"
                        wire:click="openEditBooking({{ $viewBooking['id'] }})">
                    <i class="bi bi-pencil"></i> تعديل
                </button>
                <button type="button" class="btn btn-danger"
                        data-bs-dismiss="modal"
                        wire:click="requestCancelBooking({{ $viewBooking['id'] }})">
                    <i class="bi bi-x-circle"></i> إلغاء الحجز
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Modal: تعديل وفد
     ═══════════════════════════════════════════════════════════════ --}}

<div wire:ignore.self class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> تعديل بيانات الوفد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="updateBooking">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الضيف <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('editGuestName') is-invalid @enderror"
                               wire:model="editGuestName">
                        @error('editGuestName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('editGuestPhone') is-invalid @enderror"
                               wire:model="editGuestPhone" dir="ltr">
                        @error('editGuestPhone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     CSS
     ═══════════════════════════════════════════════════════════════ --}}

<style>
    /* عنوان الفعالية */
    .event-title-card {
        background: linear-gradient(135deg, #0C4A6E 0%, #075985 100%);
        color: #fff;
        border: none;
    }
    .event-icon-circle {
        width: 56px; height: 56px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: #C9A445;
    }
    .event-main-title { color: #fff; font-weight: 700; }
    .event-title-card .text-muted { color: rgba(255,255,255,0.8) !important; }

    /* Banner التحذير */
    .booking-paused-banner {
        background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
        border: 2px solid #F59E0B;
    }
    .paused-icon {
        width: 56px; height: 56px;
        background: #F59E0B;
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
    }

    /* الإحصائيات */
    .stat-box {
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        border: 2px solid #e5e7eb;
        background: #fff;
    }
    .stat-number { font-size: 28px; font-weight: 700; line-height: 1; }
    .stat-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .stat-box-total { border-color: #0C4A6E; }
    .stat-box-total .stat-number { color: #0C4A6E; }
    .stat-box-vip { border-color: #C9A445; background: #FEF9E7; }
    .stat-box-vip .stat-number { color: #C9A445; }
    .stat-box-public { border-color: #6366F1; background: #EEF2FF; }
    .stat-box-public .stat-number { color: #6366F1; }
    .stat-box-available { border-color: #10B981; background: #ECFDF5; }
    .stat-box-available .stat-number { color: #10B981; }

    /* الـ legend */
    .legend-row {
        display: flex; flex-wrap: wrap; gap: 16px;
        padding: 12px; background: #f9fafb; border-radius: 8px;
        font-size: 13px;
    }
    .legend-item { display: flex; align-items: center; gap: 8px; }
    .legend-color {
        width: 18px; height: 18px;
        border-radius: 4px;
        display: inline-block;
        border: 1.5px solid rgba(0,0,0,0.1);
    }
    .legend-vip { background: #C9A445; }
    .legend-public { background: #6366F1; }
    .legend-available { background: #ffffff; border-color: #d1d5db; }

    /* القسم */
    .section-badge {
        background: #0C4A6E;
        color: #fff;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 700;
    }

    /* صف المقاعد */
    .seat-row-container {
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .row-label {
        flex-shrink: 0;
        width: 60px;
        padding: 4px 8px;
        background: #f3f4f6;
        border-radius: 6px;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: #4b5563;
        margin-top: 8px;
    }
    .seats-scroll-row {
        flex: 1;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 0 8px 0;
        scroll-behavior: smooth;
    }
    .seats-scroll-row::-webkit-scrollbar { height: 6px; }
    .seats-scroll-row::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .seats-scroll-row::-webkit-scrollbar-thumb { background: #C9A445; border-radius: 3px; }

    /* المقعد */
    .seat-item-wrapper {
        position: relative;
        flex-shrink: 0;
        text-align: center;
    }
    .seat-item {
        width: 36px; height: 40px;
        position: relative;
        cursor: pointer;
        transition: transform 0.15s;
    }
    .seat-chair { width: 100%; height: 100%; position: relative; }
    .chair-back {
        position: absolute; top: 0; left: 4px; right: 4px;
        height: 18px;
        background: currentColor;
        border-radius: 4px 4px 0 0;
    }
    .chair-cushion {
        position: absolute; bottom: 0; left: 0; right: 0;
        height: 22px;
        background: currentColor;
        border-radius: 0 0 6px 6px;
        opacity: 0.85;
    }
    .seat-number {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
        font-weight: 600;
    }

    /* حالات المقعد */
    .seat-item.available { color: #d1d5db; }
    .available-wrapper:hover .seat-item.available { color: #10B981; transform: scale(1.1); }

    .seat-item.booked-vip { color: #C9A445; }
    .booked-wrapper { cursor: pointer; }
    .booked-wrapper:hover .seat-item { transform: scale(1.1); }

    .seat-item.booked-public { color: #6366F1; opacity: 0.7; cursor: not-allowed; }

    .seat-item.paused { color: #9CA3AF; cursor: not-allowed; opacity: 0.5; }

    /* tooltip اسم الضيف */
    .seat-name-tooltip {
        position: absolute;
        bottom: -22px;
        left: 50%;
        transform: translateX(-50%);
        background: #1F2937;
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
        z-index: 10;
    }
    .booked-wrapper:hover .seat-name-tooltip { opacity: 1; bottom: -28px; }

    /* info row في modal */
    .info-row {
        display: flex; gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-weight: 600; color: #6b7280; min-width: 100px; }
    .info-value { color: #1f2937; }
</style>

</div>
