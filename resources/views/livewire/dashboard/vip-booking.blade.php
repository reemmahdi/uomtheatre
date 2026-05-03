<div>

{{-- معلومات الفعالية --}}
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1" style="color: var(--primary);">
                <i class="bi bi-calendar-event"></i> {{ $event->title }}
            </h5>
            <span class="text-muted">
                <i class="bi bi-calendar3"></i> {{ $event->start_datetime->format('Y-m-d') }}
                <span class="me-2">|</span>
                <i class="bi bi-clock"></i> {{ $event->start_datetime->format('H:i') }}
            </span>
        </div>
        <div class="text-end">
            <div class="mb-1">
                <strong style="color: #C9A445; font-size: 28px;">{{ $bookings->count() }}</strong>
                <span class="text-muted"> / {{ config('theatre.vip_seats') }}</span>
            </div>
            <small class="text-muted">مقعد وفود محجوز</small>
        </div>
    </div>
    <div class="progress mt-3" style="height: 8px; border-radius: 4px;">
        <div class="progress-bar"
             style="width: {{ (config('theatre.vip_seats') > 0) ? ($bookings->count() / config('theatre.vip_seats')) * 100 : 0 }}%; background: linear-gradient(135deg, #E4C05E, #C9A445);"></div>
    </div>
</div>

{{-- ✨ 🆕 Banner تحذيري عند إيقاف الحجز --}}
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
                يمكن استئناف الحجز من شاشة الفعاليات.
            </p>
        </div>
    </div>
</div>
@endif

{{-- دليل الألوان --}}
<div class="card-custom p-3 mb-4">
    <div class="d-flex gap-4 flex-wrap justify-content-center align-items-center">
        <span class="legend-item">
            <span class="legend-seat seat-available">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span>
            <span class="ms-2"><strong>متاح</strong> — اضغط للحجز</span>
        </span>
        <span class="legend-item">
            <span class="legend-seat seat-booked-vip">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span>
            <span class="ms-2"><strong>محجوز لضيف</strong></span>
        </span>
    </div>
</div>

{{-- مقاعد الوفود حسب القسم --}}
@php
    $sections = $vipSeats->groupBy(fn($s) => $s->section->name);
@endphp

@foreach($sections as $sectionName => $seats)
<div class="card-custom p-4 mb-4">
    <h6 class="mb-3 section-header">
        <span class="section-badge">القسم {{ $sectionName }}</span>
        <span class="text-muted">— الصف 10 ({{ $seats->count() }} مقعد)</span>
    </h6>

    {{-- ✨ شبكة المقاعد الجديدة --}}
    <div class="vip-seats-container">
        @foreach($seats as $seat)
        @php $booking = $bookings->get($seat->id); @endphp

        @if($booking)
            {{-- ✅ مقعد محجوز - بشكل كرسي ذهبي --}}
            <div class="vip-seat-wrapper">
                <div class="vip-chair seat-booked-vip" title="{{ $seat->label }} - {{ $booking->guest_name }}">
                    <div class="seat-back"></div>
                    <div class="seat-cushion"></div>
                    <div class="seat-armrest seat-armrest-left"></div>
                    <div class="seat-armrest seat-armrest-right"></div>
                </div>
                <div class="seat-info-card">
                    <div class="seat-label">{{ $seat->label }}</div>
                    <div class="seat-guest">{{ $booking->guest_name }}</div>
                    <div class="seat-actions">
                        <button wire:click="requestCancelBooking({{ $booking->id }})"
                                class="btn-action btn-cancel"
                                title="إلغاء الحجز">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- ✨ مقعد متاح - بشكل كرسي قابل للضغط (إلا إذا كان الحجز موقوف) --}}
            @if($event->is_booking_paused)
                {{-- 🛑 الحجز موقوف - عرض كرسي معطّل بدون تفاعل --}}
                <div class="vip-seat-wrapper available booking-disabled" title="الحجز موقوف مؤقتاً">
                    <div class="vip-chair seat-available seat-disabled">
                        <div class="seat-back"></div>
                        <div class="seat-cushion"></div>
                        <div class="seat-armrest seat-armrest-left"></div>
                        <div class="seat-armrest seat-armrest-right"></div>
                    </div>
                    <div class="seat-info-card available-card disabled-card">
                        <div class="seat-label">{{ $seat->label }}</div>
                        <div class="seat-status">
                            <i class="bi bi-pause-circle"></i> موقوف
                        </div>
                    </div>
                </div>
            @else
                {{-- ✅ الحجز مفتوح - كرسي تفاعلي --}}
                <div class="vip-seat-wrapper available"
                     wire:click="selectSeat({{ $seat->id }})"
                     data-bs-toggle="modal"
                     data-bs-target="#bookSeatModal">
                    <div class="vip-chair seat-available" title="اضغط لحجز {{ $seat->label }}">
                        <div class="seat-back"></div>
                        <div class="seat-cushion"></div>
                        <div class="seat-armrest seat-armrest-left"></div>
                        <div class="seat-armrest seat-armrest-right"></div>
                    </div>
                    <div class="seat-info-card available-card">
                        <div class="seat-label">{{ $seat->label }}</div>
                        <div class="seat-status">
                            <i class="bi bi-plus-circle"></i> اضغط للحجز
                        </div>
                    </div>
                </div>
            @endif
        @endif
        @endforeach
    </div>
</div>
@endforeach

{{-- قائمة الوفود المحجوزين --}}
@if($bookings->count() > 0)
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-people"></i> قائمة الوفود ({{ $bookings->count() }})</h6>
        <div class="d-flex gap-2 align-items-center">
            {{-- ✨ مؤشّر التقدّم (يظهر أثناء الإرسال) --}}
            <span id="wa-progress" class="text-muted small" style="display:none;">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                جارٍ الإرسال: <span id="wa-progress-current">0</span>/<span id="wa-progress-total">0</span>
            </span>
            {{-- ✨ زر إرسال الكل المحسّن (Sequential + Progress) --}}
            <button id="send-all-whatsapp"
                    class="btn btn-sm"
                    style="background:#25D366;color:#fff;"
                    onclick="sendAllWhatsApp()">
                <i class="bi bi-whatsapp"></i> إرسال الكل عبر واتساب
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>الضيف</th><th>الجوال</th><th>المقعد</th><th>القسم</th><th>الإجراءات</th></tr></thead>
            <tbody>
                @foreach($bookings as $booking)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $booking->guest_name }}</strong></td>
                    <td dir="ltr">{{ $booking->guest_phone }}</td>
                    <td><span class="badge" style="background: linear-gradient(135deg, #E4C05E, #C9A445); color: #5a4500;">{{ $booking->seat->label }}</span></td>
                    <td>القسم {{ $booking->seat->section->name }}</td>
                    <td>
                        {{-- ✨ زر الإلغاء فقط (تم حذف زر الواتساب الفردي) --}}
                        <button wire:click="requestCancelBooking({{ $booking->id }})"
                                class="btn btn-sm btn-outline-danger"
                                title="إلغاء الحجز">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        {{-- ✨ رابط واتساب مخفي (يُستخدم بواسطة زر إرسال الكل) --}}
                        <span class="wa-link-data"
                              data-link="{{ $this->getWhatsAppLink($booking->id) }}"
                              data-name="{{ $booking->guest_name }}"
                              style="display:none;"></span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ✨ سكريبت إرسال الكل بالتسلسل (Sequential Tab Opening) --}}
<script>
function sendAllWhatsApp() {
    const links = document.querySelectorAll('.wa-link-data');
    const total = links.length;

    if (total === 0) {
        alert('لا توجد دعوات للإرسال');
        return;
    }

    // تأكيد قبل الإرسال
    if (!confirm(`سيتم فتح ${total} نافذة واتساب بالتتابع.\nيرجى السماح للنوافذ المنبثقة عند طلب المتصفح.\nهل تريد المتابعة؟`)) {
        return;
    }

    // إظهار مؤشّر التقدّم وتعطيل الزر
    const progressEl = document.getElementById('wa-progress');
    const currentEl = document.getElementById('wa-progress-current');
    const totalEl = document.getElementById('wa-progress-total');
    const btn = document.getElementById('send-all-whatsapp');

    totalEl.textContent = total;
    currentEl.textContent = 0;
    progressEl.style.display = 'inline-block';
    btn.disabled = true;

    // فتح كل رابط بفاصل 300ms (لتفادي حجب المتصفح للنوافذ المنبثقة)
    links.forEach((link, index) => {
        setTimeout(() => {
            window.open(link.dataset.link, '_blank');
            currentEl.textContent = index + 1;

            // عند انتهاء الإرسال
            if (index === total - 1) {
                setTimeout(() => {
                    progressEl.style.display = 'none';
                    btn.disabled = false;
                    alert(`تم فتح ${total} نافذة واتساب بنجاح`);
                }, 500);
            }
        }, index * 300);
    });
}
</script>
@endif

{{-- زر الرجوع --}}
<div class="text-center mt-3">
    <a href="{{ route('dashboard.events') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right"></i> الرجوع للفعاليات
    </a>
</div>

{{-- نافذة حجز مقعد --}}
<div class="modal fade" id="bookSeatModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-star-fill" style="color: #E4C05E;"></i> حجز مقعد وفود
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @error('guestName')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('guestPhone')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label fw-bold">اسم الضيف <span class="text-danger">*</span></label>
                    <input type="text" wire:model="guestName" class="form-control" placeholder="الاسم الكامل للضيف">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الجوال <span class="text-danger">*</span></label>
                    <input type="text" wire:model="guestPhone" class="form-control" dir="ltr" placeholder="07701234567">
                    <small class="text-muted">سيتم إرسال دعوة واتساب لهذا الرقم</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="bookSeat" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="bookSeat"><i class="bi bi-check-lg"></i> حجز المقعد</span>
                    <span wire:loading wire:target="bookSeat"><span class="wire-loading"></span> جاري الحجز...</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ✨ التنسيقات الجديدة للكراسي --}}
<style>
    /* عنوان القسم */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-badge {
        background: linear-gradient(135deg, #0369A1, #0284C7);
        color: #fff;
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 1px;
        box-shadow: 0 2px 4px rgba(3, 105, 161, 0.2);
    }

    /* ═══════════════════════════════════════════
       حاوية المقاعد
       ═══════════════════════════════════════════ */
    .vip-seats-container {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        justify-content: center;
        padding: 15px 0;
    }

    /* غلاف المقعد - يحتوي على الكرسي والمعلومات */
    .vip-seat-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s ease;
    }

    .vip-seat-wrapper.available {
        cursor: pointer;
    }

    .vip-seat-wrapper.available:hover {
        transform: translateY(-4px);
    }

    /* ═══════════════════════════════════════════
       ✨ الكرسي ثلاثي الأبعاد
       ═══════════════════════════════════════════ */
    .vip-chair {
        position: relative;
        width: 60px;
        height: 65px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    /* مسند الظهر العالي */
    .vip-chair .seat-back {
        position: absolute;
        top: 0;
        left: 8px;
        right: 8px;
        height: 35px;
        border-radius: 12px 12px 3px 3px;
        background-color: var(--seat-color);
        box-shadow:
            inset 0 2px 0 rgba(255, 255, 255, 0.3),
            inset 0 -2px 0 rgba(0, 0, 0, 0.15),
            0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* خط زخرفي على مسند الظهر */
    .vip-chair .seat-back::before {
        content: '';
        position: absolute;
        top: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 60%;
        height: 2px;
        background: rgba(0, 0, 0, 0.18);
        border-radius: 1px;
    }

    .vip-chair .seat-back::after {
        content: '';
        position: absolute;
        top: 14px;
        left: 50%;
        transform: translateX(-50%);
        width: 40%;
        height: 2px;
        background: rgba(0, 0, 0, 0.12);
        border-radius: 1px;
    }

    /* الجلسة العريضة */
    .vip-chair .seat-cushion {
        position: absolute;
        bottom: 4px;
        left: 0;
        right: 0;
        height: 28px;
        border-radius: 6px 6px 14px 14px;
        background-color: var(--seat-color);
        box-shadow:
            inset 0 2px 0 rgba(255, 255, 255, 0.35),
            inset 0 -4px 4px rgba(0, 0, 0, 0.18),
            0 4px 6px rgba(0, 0, 0, 0.12);
    }

    /* ظل تحت الكرسي */
    .vip-chair .seat-cushion::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 6px;
        right: 6px;
        height: 4px;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 50%;
        filter: blur(2px);
    }

    /* مسانيد الذراعين */
    .vip-chair .seat-armrest {
        position: absolute;
        bottom: 4px;
        width: 8px;
        height: 38px;
        background-color: var(--seat-color);
        filter: brightness(0.7);
        border-radius: 4px 2px 4px 4px;
    }

    .vip-chair .seat-armrest-left { left: 0; }
    .vip-chair .seat-armrest-right { right: 0; }

    /* ═══════════════════════════════════════════
       ألوان الكراسي
       ═══════════════════════════════════════════ */

    /* مقعد متاح - أزرق Midnight Ocean */
    .seat-available {
        --seat-color: #0369A1;
    }

    .vip-seat-wrapper.available:hover .vip-chair {
        --seat-color: #0284C7;
        transform: scale(1.08);
        filter: drop-shadow(0 6px 12px rgba(3, 105, 161, 0.3));
    }

    /* مقعد محجوز - ذهبي Theme */
    .seat-booked-vip {
        --seat-color: #E4C05E;
    }

    .seat-booked-vip .seat-back,
    .seat-booked-vip .seat-cushion {
        background: linear-gradient(135deg, #E4C05E, #C9A445);
    }

    .seat-booked-vip .seat-back {
        box-shadow:
            inset 0 2px 0 rgba(255, 255, 255, 0.4),
            inset 0 -2px 0 rgba(180, 83, 9, 0.3),
            0 2px 4px rgba(228, 192, 94, 0.4);
    }

    .seat-booked-vip .seat-armrest {
        background-color: #C9A445;
        filter: brightness(0.85);
    }

    /* ═══════════════════════════════════════════
       بطاقة معلومات المقعد
       ═══════════════════════════════════════════ */
    .seat-info-card {
        width: 130px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
        text-align: center;
        font-size: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
    }

    .seat-info-card.available-card {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border-color: #0369A1;
        cursor: pointer;
    }

    .vip-seat-wrapper.available:hover .seat-info-card.available-card {
        background: linear-gradient(135deg, #0369A1, #0284C7);
        color: #fff;
        border-color: #075985;
        box-shadow: 0 4px 12px rgba(3, 105, 161, 0.3);
    }

    .seat-info-card .seat-label {
        font-weight: 700;
        font-size: 13px;
        color: var(--primary, #0C4A6E);
        margin-bottom: 4px;
    }

    .vip-seat-wrapper.available:hover .seat-info-card.available-card .seat-label {
        color: #fff;
    }

    .seat-info-card .seat-guest {
        color: #5a4500;
        font-weight: 600;
        font-size: 11px;
        background: linear-gradient(135deg, #fef9c3, #fde68a);
        padding: 3px 6px;
        border-radius: 6px;
        margin: 4px 0;
        word-wrap: break-word;
    }

    .seat-info-card .seat-status {
        color: #0369A1;
        font-size: 11px;
        font-weight: 600;
    }

    .vip-seat-wrapper.available:hover .seat-info-card.available-card .seat-status {
        color: #fff;
    }

    /* ═══════════════════════════════════════════
       أزرار الإجراءات (للمقاعد المحجوزة)
       ═══════════════════════════════════════════ */
    .seat-actions {
        display: flex;
        gap: 4px;
        margin-top: 6px;
    }

    .btn-action {
        flex: 1;
        padding: 5px 0;
        border-radius: 6px;
        border: none;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-action.btn-whatsapp {
        background: #25D366;
        color: #fff;
    }

    .btn-action.btn-whatsapp:hover {
        background: #128C7E;
        transform: scale(1.05);
    }

    .btn-action.btn-cancel {
        background: #fff;
        color: #dc2626;
        border: 1px solid #fca5a5;
    }

    .btn-action.btn-cancel:hover {
        background: #dc2626;
        color: #fff;
        border-color: #dc2626;
        transform: scale(1.05);
    }

    /* ═══════════════════════════════════════════
       دليل الألوان
       ═══════════════════════════════════════════ */
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .legend-seat {
        position: relative;
        width: 30px;
        height: 32px;
        display: inline-block;
        flex-shrink: 0;
    }

    .legend-seat .seat-back {
        position: absolute;
        top: 0;
        left: 4px;
        right: 4px;
        height: 16px;
        border-radius: 6px 6px 2px 2px;
        background-color: var(--seat-color);
    }

    .legend-seat .seat-cushion {
        position: absolute;
        bottom: 2px;
        left: 0;
        right: 0;
        height: 14px;
        border-radius: 3px 3px 6px 6px;
        background-color: var(--seat-color);
    }

    .legend-seat.seat-booked-vip .seat-back,
    .legend-seat.seat-booked-vip .seat-cushion {
        background: linear-gradient(135deg, #E4C05E, #C9A445);
    }

    /* ═══════════════════════════════════════════
       Responsive
       ═══════════════════════════════════════════ */
    @media (max-width: 768px) {
        .vip-seats-container {
            gap: 10px;
        }

        .vip-chair {
            width: 50px;
            height: 55px;
        }

        .vip-chair .seat-back {
            height: 28px;
        }

        .vip-chair .seat-cushion {
            height: 22px;
        }

        .seat-info-card {
            width: 105px;
            font-size: 11px;
        }
    }

    /* ═══════════════════════════════════════════
       ✨ 🆕 Banner تحذيري للحجز الموقوف
       ═══════════════════════════════════════════ */
    .booking-paused-banner {
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        border-right: 5px solid #F59E0B;
        animation: pausedPulse 2.5s ease-in-out infinite;
    }

    .paused-icon {
        font-size: 48px;
        color: #F59E0B;
        animation: pausedRotate 3s ease-in-out infinite;
    }

    @keyframes pausedPulse {
        0%, 100% {
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
        }
        50% {
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
        }
    }

    @keyframes pausedRotate {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }

    /* ═══════════════════════════════════════════
       ✨ 🆕 الكراسي المعطّلة بسبب إيقاف الحجز
       ═══════════════════════════════════════════ */
    .vip-seat-wrapper.booking-disabled {
        cursor: not-allowed !important;
        pointer-events: none;
    }

    .vip-seat-wrapper.booking-disabled:hover {
        transform: none !important;
    }

    .vip-chair.seat-disabled {
        opacity: 0.45;
        filter: grayscale(70%);
    }

    .vip-chair.seat-disabled .seat-back,
    .vip-chair.seat-disabled .seat-cushion,
    .vip-chair.seat-disabled .seat-armrest {
        --seat-color: #9CA3AF !important;
        background-color: #9CA3AF !important;
    }

    .seat-info-card.disabled-card {
        background: #F3F4F6 !important;
        border-color: #9CA3AF !important;
    }

    .seat-info-card.disabled-card .seat-status {
        color: #92400E !important;
        font-weight: 600;
    }
</style>

@script
<script>
    $wire.on('close-modal', () => {
        document.querySelectorAll('.modal').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
    });
</script>
@endscript

</div>
