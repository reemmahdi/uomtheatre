<div>

{{-- ✨ عنوان الفعالية (بارز وواضح) --}}
<div class="card-custom p-4 mb-4 event-title-card">
    <div class="d-flex align-items-center gap-3">
        <div class="event-icon-circle">
            <i class="bi bi-calendar-event"></i>
        </div>
        <div class="flex-grow-1">
            <div class="small text-muted mb-1">الفعالية الحالية</div>
            <h3 class="mb-0 event-main-title">{{ $event->title }}</h3>
        </div>
    </div>
</div>

{{-- ✨ Banner تحذيري عند إيقاف الحجز --}}
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

{{-- مقاعد الوفود حسب القسم --}}
@php
    $sections = $vipSeats->groupBy(fn($s) => $s->section->name);
@endphp

@foreach($sections as $sectionName => $seats)
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0">
            <span class="section-badge">القسم {{ $sectionName }}</span>
            <span class="text-muted small ms-2">— {{ $seats->count() }} مقعد</span>
        </h6>
        <span class="small text-muted">
            <i class="bi bi-arrow-left-right"></i> اسحب للتمرير
        </span>
    </div>

    {{-- ✨ صف المقاعد الأفقي (سكرول) --}}
    <div class="vip-seats-scroll-container">
        <div class="vip-seats-row">
            @foreach($seats as $seat)
            @php $booking = $bookings->get($seat->id); @endphp

            @if($booking)
                {{-- ✅ مقعد محجوز --}}
                <div class="seat-item-wrapper booked-wrapper">
                    <div class="seat-item booked"
                         title="{{ $seat->label }} - {{ $booking->guest_name }}">
                        <div class="seat-chair">
                            <div class="chair-back"></div>
                            <div class="chair-cushion"></div>
                        </div>
                    </div>
                    <div class="seat-number">{{ $seat->seat_number }}</div>
                    <div class="seat-name-tooltip">{{ $booking->guest_name }}</div>
                </div>
            @else
                {{-- ⚪ مقعد متاح --}}
                @if($event->is_booking_paused)
                    <div class="seat-item-wrapper">
                        <div class="seat-item paused" title="الحجز موقوف">
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
                         data-bs-target="#bookSeatModal">
                        <div class="seat-item available" title="اضغط لحجز {{ $seat->label }}">
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

    {{-- ✨ دليل الألوان (مدمج) --}}
    <div class="d-flex gap-3 justify-content-center mt-3 flex-wrap pt-3 border-top">
        <div class="d-flex align-items-center gap-2">
            <span class="legend-box legend-available"></span>
            <small><strong>متاح</strong></small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="legend-box legend-booked"></span>
            <small><strong>محجوز</strong></small>
        </div>
        @if($event->is_booking_paused)
        <div class="d-flex align-items-center gap-2">
            <span class="legend-box legend-paused"></span>
            <small><strong>موقوف</strong></small>
        </div>
        @endif
    </div>
</div>
@endforeach

{{-- قائمة الوفود المحجوزين --}}
@if($bookings->count() > 0)
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-people"></i> قائمة الوفود ({{ $bookings->count() }})</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button id="open-send-all-modal"
                    type="button"
                    class="btn btn-sm"
                    style="background:#25D366;color:#fff;font-weight:700;"
                    data-bs-toggle="modal"
                    data-bs-target="#sendAllWhatsAppModal">
                <i class="bi bi-whatsapp"></i> إرسال للكل
            </button>
        </div>
    </div>

    {{-- ✨ ملاحظة توضيحية --}}
    <div class="alert alert-info py-2 mb-3 small">
        <i class="bi bi-info-circle"></i>
        <strong>ملاحظة:</strong> تأكد من تسجيل دخولك في
        <a href="https://web.whatsapp.com" target="_blank" style="color: #075985; text-decoration: underline;">واتساب ويب</a>
        قبل الإرسال. عند الضغط على زر الإرسال، تفتح نافذة الواتساب مع الرسالة جاهزة — اضغط
        <kbd>Enter</kbd> أو زر الإرسال داخل الواتساب لإتمام الإرسال.
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle vip-guests-table">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th>الضيف</th>
                    <th style="width: 160px;" class="text-center">رقم الجوال</th>
                    <th style="width: 90px;" class="text-center">المقعد</th>
                    <th style="width: 100px;" class="text-center">القسم</th>
                    <th style="width: 140px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                @php $waLink = $this->getWhatsAppLink($booking->id); @endphp
                <tr>
                    <td class="text-center"><strong style="color: #0C4A6E;">{{ $loop->iteration }}</strong></td>
                    <td><strong>{{ $booking->guest_name }}</strong></td>
                    <td class="text-center phone-cell">
                        <span dir="ltr" class="phone-number">{{ $booking->guest_phone }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge" style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; padding: 6px 12px;">
                            {{ $booking->seat->label }}
                        </span>
                    </td>
                    <td class="text-center">القسم {{ $booking->seat->section->name }}</td>
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            {{-- 🟢 إرسال واتساب --}}
                            <a href="{{ $waLink }}"
                               target="_blank"
                               rel="noopener"
                               class="btn-action-small btn-whatsapp-small wa-link"
                               title="إرسال دعوة واتساب لـ {{ $booking->guest_name }}"
                               data-link="{{ $waLink }}"
                               data-name="{{ $booking->guest_name }}">
                                <i class="bi bi-whatsapp"></i>
                            </a>

                            {{-- ❌ حذف --}}
                            <button type="button"
                                    wire:click="requestCancelBooking({{ $booking->id }})"
                                    class="btn-action-small btn-delete-small"
                                    title="إلغاء الحجز">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{--  ✨ Modal: إرسال دعوات الواتساب الجماعي (واحد واحد بضغطة يدوية)  --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="sendAllWhatsAppModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-bottom: 2px solid #25D366;">
                <h5 class="modal-title" style="color: #065f46;">
                    <i class="bi bi-whatsapp"></i> إرسال دعوات الواتساب
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                {{-- ✨ شريط التقدم + زر فتح الكل --}}
                <div class="wa-progress-section p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong style="color: #065f46;">
                            <i class="bi bi-check2-circle"></i>
                            التقدم: <span id="wa-sent-count">0</span> من <span id="wa-total-count">{{ $bookings->count() }}</span>
                        </strong>
                        <div class="d-flex gap-2">
                            {{-- زر فتح الكل تلقائياً --}}
                            <button type="button"
                                    id="wa-bulk-open-btn"
                                    class="btn btn-sm"
                                    style="background:#25D366;color:#fff;font-weight:700;"
                                    onclick="bulkOpenAllWhatsApp()">
                                <i class="bi bi-lightning-charge-fill"></i> فتح كل التبويبات تلقائياً
                            </button>
                            <button type="button" id="wa-reset-btn" class="btn btn-sm btn-outline-secondary" onclick="resetWhatsAppProgress()">
                                <i class="bi bi-arrow-counterclockwise"></i> إعادة التعيين
                            </button>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px;">
                        <div id="wa-progress-bar"
                             class="progress-bar"
                             role="progressbar"
                             style="width: 0%; background: linear-gradient(135deg, #25D366, #128C7E); border-radius: 6px; transition: width 0.4s ease;">
                        </div>
                    </div>

                    {{-- ✨ تنبيه حالة الـ popup blocker --}}
                    <div id="wa-popup-warning" class="alert alert-warning mt-2 mb-0 py-2 small" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>تنبيه:</strong> المتصفح حجب بعض النوافذ. اضغط على أيقونة
                        <i class="bi bi-shield-exclamation"></i>
                        بشريط العنوان (يمين الرابط) واختر "السماح دائماً للنوافذ المنبثقة من هذا الموقع"، ثم أعد المحاولة.
                        أو استخدم زر "إرسال" الفردي لكل ضيف بالأسفل.
                    </div>
                </div>

                {{-- ✨ قائمة الضيوف --}}
                <div class="wa-guests-list">
                    @foreach($bookings as $booking)
                    @php $waLink = $this->getWhatsAppLink($booking->id); @endphp
                    <div class="wa-guest-item" data-booking-id="{{ $booking->id }}">
                        <div class="wa-guest-number">{{ $loop->iteration }}</div>
                        <div class="wa-guest-info">
                            <div class="wa-guest-name">{{ $booking->guest_name }}</div>
                            <div class="wa-guest-phone" dir="ltr">{{ $booking->guest_phone }}</div>
                        </div>
                        <div class="wa-guest-seat">
                            <span class="badge" style="background: #0C4A6E; color: #fff;">{{ $booking->seat->label }}</span>
                        </div>
                        <div class="wa-guest-action">
                            <a href="{{ $waLink }}"
                               target="_blank"
                               rel="noopener"
                               class="btn btn-sm wa-send-btn"
                               data-booking-id="{{ $booking->id }}"
                               onclick="markAsSent(this, {{ $booking->id }})">
                                <i class="bi bi-whatsapp"></i> إرسال
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                <small class="text-muted me-auto">
                    <i class="bi bi-info-circle"></i>
                    اضغط زر "إرسال" بجانب كل ضيف. يفتح الواتساب مع الرسالة جاهزة — اضغط Enter للإرسال.
                </small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> إغلاق
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ✨ سكريبت إدارة الإرسال الفردي مع حفظ التقدم --}}
<script>
(function() {
    // مفتاح فريد لكل فعالية حتى لا يختلط التقدم بين الفعاليات
    const STORAGE_KEY = 'wa_sent_event_{{ $event->id }}';

    // قراءة قائمة الـ IDs المُرسَلة من LocalStorage
    function getSentIds() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    // حفظ قائمة الـ IDs المُرسَلة
    function saveSentIds(ids) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
        } catch (e) {}
    }

    // تحديث شريط التقدم وعدّاد الإرسال
    function updateProgress() {
        const sentIds = getSentIds();
        const total = document.querySelectorAll('.wa-guest-item').length;
        const percent = total > 0 ? Math.round((sentIds.length / total) * 100) : 0;

        const sentCountEl = document.getElementById('wa-sent-count');
        const progressBar = document.getElementById('wa-progress-bar');

        if (sentCountEl) sentCountEl.textContent = sentIds.length;
        if (progressBar) progressBar.style.width = percent + '%';
    }

    // تطبيق حالة "تم الإرسال" على عنصر معيّن
    function applySentState(bookingId) {
        const item = document.querySelector('.wa-guest-item[data-booking-id="' + bookingId + '"]');
        if (!item) return;

        item.classList.add('wa-sent');
        const btn = item.querySelector('.wa-send-btn');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> تم الإرسال';
            btn.classList.add('wa-sent-btn');
            // الزر يبقى يشتغل لو حاب يرسلها مرة ثانية
        }
    }

    // ✨ فتح كل تبويبات الواتساب تلقائياً دفعة وحدة
    window.bulkOpenAllWhatsApp = function() {
        const btn = document.getElementById('wa-bulk-open-btn');
        const warningEl = document.getElementById('wa-popup-warning');

        // إخفاء التنبيه القديم
        if (warningEl) warningEl.style.display = 'none';

        // جمع كل الأزرار اللي ما تم إرسالها بعد
        const sentIds = getSentIds();
        const items = document.querySelectorAll('.wa-guest-item');
        const pending = [];

        items.forEach(function(item) {
            const bookingId = parseInt(item.dataset.bookingId);
            if (!sentIds.includes(bookingId)) {
                const link = item.querySelector('.wa-send-btn');
                if (link) {
                    pending.push({ id: bookingId, url: link.href });
                }
            }
        });

        if (pending.length === 0) {
            alert('تم إرسال كل الدعوات بالفعل ✓\n\nلإعادة الإرسال، اضغط "إعادة التعيين" أولاً.');
            return;
        }

        // تأكيد قبل الفتح
        if (!confirm('سيتم فتح ' + pending.length + ' تبويب واتساب دفعة واحدة.\n\nملاحظة مهمة: قد يطلب المتصفح "السماح بالنوافذ المنبثقة" أول مرة.\n\nهل تريد المتابعة؟')) {
            return;
        }

        // تعطيل الزر مؤقتاً
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جارٍ الفتح...';
        }

        // فتح كل التبويبات بفاصل صغير (50ms) - أسرع من العين البشرية لكن يعطي المتصفح وقت
        let blockedCount = 0;
        let openedCount = 0;

        pending.forEach(function(entry, index) {
            setTimeout(function() {
                const newWindow = window.open(entry.url, '_blank');

                if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                    // المتصفح حجب النافذة
                    blockedCount++;
                } else {
                    // فُتحت بنجاح
                    openedCount++;
                    // علامة كمُرسَل
                    const updatedSent = getSentIds();
                    if (!updatedSent.includes(entry.id)) {
                        updatedSent.push(entry.id);
                        saveSentIds(updatedSent);
                    }
                    applySentState(entry.id);
                }

                // عند الانتهاء من كل التبويبات
                if (index === pending.length - 1) {
                    setTimeout(function() {
                        updateProgress();
                        scrollToNextUnsent();

                        // إعادة تفعيل الزر
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> فتح كل التبويبات تلقائياً';
                        }

                        // عرض النتيجة
                        if (blockedCount > 0) {
                            // إظهار تنبيه الـ popup blocker
                            if (warningEl) warningEl.style.display = 'block';
                            alert('⚠️ المتصفح حجب ' + blockedCount + ' نافذة من أصل ' + pending.length + '.\n\n• تم فتح: ' + openedCount + '\n• محجوب: ' + blockedCount + '\n\nاسمح بالنوافذ المنبثقة من إعدادات الموقع، ثم أعد المحاولة.\nأو استخدم زر "إرسال" الفردي لكل ضيف.');
                        } else {
                            alert('✅ تم فتح ' + openedCount + ' تبويب واتساب بنجاح.\n\nاضغط Enter في كل تبويب لإرسال الرسالة.');
                        }
                    }, 300);
                }
            }, index * 50); // 50ms بين كل نافذة - أسرع تسلسل ممكن
        });
    };

    // عند الضغط على زر "إرسال" الفردي - يفتح الرابط ويعلّمه كمُرسَل
    window.markAsSent = function(linkEl, bookingId) {
        const sentIds = getSentIds();
        if (!sentIds.includes(bookingId)) {
            sentIds.push(bookingId);
            saveSentIds(sentIds);
        }

        // نطبّق الحالة بعد فترة قصيرة (حتى يفتح التبويب الجديد أولاً)
        setTimeout(function() {
            applySentState(bookingId);
            updateProgress();
            scrollToNextUnsent();
        }, 100);

        // ما نمنع الـ default - الرابط يفتح تبويب جديد طبيعياً
        return true;
    };

    // التمرير التلقائي للضيف التالي اللي لسة ما اتم إرساله
    function scrollToNextUnsent() {
        const items = document.querySelectorAll('.wa-guest-item:not(.wa-sent)');
        if (items.length > 0) {
            items[0].classList.add('wa-next');
            // نزيل الـ highlight عن الباقي
            document.querySelectorAll('.wa-guest-item.wa-next').forEach(function(el, i) {
                if (i > 0) el.classList.remove('wa-next');
            });
            items[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // إعادة تعيين التقدم
    window.resetWhatsAppProgress = function() {
        if (!confirm('هل تريد إعادة تعيين التقدم؟ ستُمسح كل علامات "تم الإرسال" لهذه الفعالية.')) {
            return;
        }
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}

        document.querySelectorAll('.wa-guest-item').forEach(function(item) {
            item.classList.remove('wa-sent', 'wa-next');
            const btn = item.querySelector('.wa-send-btn');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-whatsapp"></i> إرسال';
                btn.classList.remove('wa-sent-btn');
            }
        });

        updateProgress();
        scrollToNextUnsent();
    };

    // تطبيق الحالة المحفوظة عند فتح الـ Modal
    document.addEventListener('shown.bs.modal', function(e) {
        if (e.target.id === 'sendAllWhatsAppModal') {
            const sentIds = getSentIds();
            sentIds.forEach(applySentState);
            updateProgress();
            scrollToNextUnsent();
        }
    });
})();
</script>
@endif

{{-- زر الرجوع --}}
<div class="text-center mt-3">
    <a href="{{ route('dashboard.vip-events') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right"></i> الرجوع للفعاليات
    </a>
</div>

{{-- نافذة حجز مقعد --}}
<div class="modal fade" id="bookSeatModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-star-fill" style="color: #0C4A6E;"></i> حجز مقعد وفود
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


{{-- ✨ التنسيقات --}}
<style>
    /* ════════════ كرت عنوان الفعالية ════════════ */
    .event-title-card {
        background: linear-gradient(135deg, #0C4A6E 0%, #075985 100%);
        border: none;
        box-shadow: 0 8px 25px rgba(12, 74, 110, 0.25);
    }

    .event-icon-circle {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
        flex-shrink: 0;
    }

    .event-title-card .text-muted {
        color: rgba(255, 255, 255, 0.75) !important;
        font-size: 13px;
    }

    .event-main-title {
        color: #fff;
        font-weight: 800;
        font-size: 24px;
        font-family: 'Cairo', 'Tajawal', sans-serif;
        line-height: 1.4;
        letter-spacing: -0.5px;
    }

    /* ════════════ Section Badge (محسّن - تباين عالي) ════════════ */
    .section-badge {
        display: inline-block;
        background: linear-gradient(135deg, #082F49 0%, #0C4A6E 100%);
        color: #fff;
        padding: 8px 18px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 15px;
        letter-spacing: 0.3px;
        box-shadow: 0 3px 8px rgba(12, 74, 110, 0.35);
        border: 2px solid #082F49;
    }

    /* ════════════ سكرول أفقي للمقاعد ════════════ */
    .vip-seats-scroll-container {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 25px 15px 15px;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .vip-seats-row {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 10px 5px 20px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    .vip-seats-row::-webkit-scrollbar {
        height: 8px;
    }

    .vip-seats-row::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 4px;
    }

    .vip-seats-row::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        border-radius: 4px;
    }

    .vip-seats-row::-webkit-scrollbar-thumb:hover {
        background: #0C4A6E;
    }

    /* ════════════ المقعد الفردي ════════════ */
    .seat-item-wrapper {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        position: relative;
        min-width: 65px;
    }

    .seat-item-wrapper.available-wrapper {
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .seat-item-wrapper.available-wrapper:hover {
        transform: translateY(-4px);
    }

    /* ════════════ شكل الكرسي ════════════ */
    .seat-item {
        width: 55px;
        height: 70px;
        position: relative;
        transition: all 0.25s ease;
    }

    .seat-chair {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .chair-back {
        width: 50px;
        height: 38px;
        border-radius: 8px 8px 4px 4px;
        margin-bottom: 2px;
    }

    .chair-cushion {
        width: 55px;
        height: 18px;
        border-radius: 4px 4px 8px 8px;
    }

    /* مقعد متاح ⚪ */
    .seat-item.available .chair-back {
        background: #fff;
        border: 2px solid #0C4A6E;
        box-shadow: 0 2px 4px rgba(12, 74, 110, 0.1);
    }

    .seat-item.available .chair-cushion {
        background: #fff;
        border: 2px solid #0C4A6E;
        border-top: none;
    }

    .available-wrapper:hover .seat-item.available .chair-back,
    .available-wrapper:hover .seat-item.available .chair-cushion {
        background: #e0f2fe;
        border-color: #075985;
    }

    /* مقعد محجوز 🟦 */
    .seat-item.booked .chair-back {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        border: 2px solid #082F49;
        box-shadow: 0 4px 8px rgba(12, 74, 110, 0.4);
    }

    .seat-item.booked .chair-cushion {
        background: linear-gradient(135deg, #075985, #0369A1);
        border: 2px solid #082F49;
        border-top: none;
    }

    /* مقعد موقوف 🔘 */
    .seat-item.paused .chair-back {
        background: #94a3b8;
        border: 2px solid #64748b;
        opacity: 0.6;
    }

    .seat-item.paused .chair-cushion {
        background: #94a3b8;
        border: 2px solid #64748b;
        border-top: none;
        opacity: 0.6;
    }

    /* رقم المقعد */
    .seat-number {
        font-weight: 800;
        color: #0C4A6E;
        font-size: 14px;
        background: #fff;
        padding: 2px 10px;
        border-radius: 12px;
        border: 1.5px solid #0C4A6E;
        min-width: 30px;
        text-align: center;
    }

    .booked-wrapper .seat-number {
        background: #0C4A6E;
        color: #fff;
    }

    /* tooltip اسم الضيف للمقعد المحجوز */
    .seat-name-tooltip {
        font-size: 11px;
        color: #475569;
        text-align: center;
        max-width: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }

    /* ════════════ دليل الألوان ════════════ */
    .legend-box {
        width: 22px;
        height: 22px;
        border-radius: 4px;
        display: inline-block;
    }

    .legend-available {
        background: #fff;
        border: 2px solid #0C4A6E;
    }

    .legend-booked {
        background: linear-gradient(135deg, #0C4A6E, #075985);
    }

    .legend-paused {
        background: #94a3b8;
    }

    /* ════════════ جدول الوفود (محسّن - متوازن) ════════════ */
    .vip-guests-table {
        table-layout: auto;
    }

    .vip-guests-table thead th {
        font-weight: 700;
        color: #0C4A6E;
        font-size: 13px;
        padding: 12px 10px;
        white-space: nowrap;
        border-bottom: 2px solid #cbd5e1;
    }

    .vip-guests-table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
    }

    /* عمود رقم الجوال - LTR مع توسيط منتظم */
    .vip-guests-table .phone-cell {
        font-family: 'Tajawal', 'Segoe UI', sans-serif;
    }

    .vip-guests-table .phone-number {
        display: inline-block;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
        color: #0C4A6E;
        font-size: 14px;
        letter-spacing: 0.5px;
        min-width: 130px;
        text-align: center;
    }

    /* ════════════ أزرار الإجراءات بالجدول ════════════ */
    .btn-action-small {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1.5px solid;
        background: #fff;
        transition: all 0.2s ease;
        font-size: 15px;
        cursor: pointer;
        text-decoration: none;
    }

    /* ✨ زر الواتساب */
    .btn-whatsapp-small {
        color: #25D366;
        border-color: #25D366;
    }

    .btn-whatsapp-small:hover {
        background: #25D366;
        color: #fff;
        transform: translateY(-2px);
        text-decoration: none;
    }

    .btn-delete-small {
        color: #DC2626;
        border-color: #DC2626;
    }

    .btn-delete-small:hover {
        background: #DC2626;
        color: #fff;
        transform: translateY(-2px);
    }

    /* ════════════ Banner تحذير الإيقاف ════════════ */
    .booking-paused-banner {
        background: linear-gradient(135deg, #fef3c7, #fef9c3);
        border: 2px solid #F59E0B;
    }

    .paused-icon {
        width: 60px;
        height: 60px;
        background: #F59E0B;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        flex-shrink: 0;
    }

    /* ════════════ Responsive ════════════ */
    @media (max-width: 768px) {
        .event-main-title {
            font-size: 18px;
        }

        .event-icon-circle {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .seat-item {
            width: 48px;
            height: 60px;
        }

        .chair-back {
            width: 44px;
            height: 32px;
        }

        .chair-cushion {
            width: 48px;
            height: 16px;
        }

        .seat-number {
            font-size: 12px;
            padding: 1px 8px;
        }

        .seat-item-wrapper {
            min-width: 55px;
            gap: 4px;
        }

        .vip-guests-table .phone-number {
            min-width: 110px;
            font-size: 13px;
        }
    }

    /* ════════════════════════════════════════════════ */
    /* ✨ Modal: قائمة إرسال الواتساب الجماعي           */
    /* ════════════════════════════════════════════════ */
    .wa-progress-section {
        background: #f0fdf4;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .wa-guests-list {
        padding: 0;
    }

    .wa-guest-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        transition: all 0.25s ease;
    }

    .wa-guest-item:hover {
        background: #f8fafc;
    }

    /* الضيف التالي - مُبرز بإطار أخضر */
    .wa-guest-item.wa-next {
        background: linear-gradient(90deg, #f0fdf4 0%, #fff 100%);
        border-right: 4px solid #25D366;
        padding-right: 14px;
    }

    /* الضيف الذي تم إرساله - بخلفية فاتحة */
    .wa-guest-item.wa-sent {
        background: #f8fafc;
        opacity: 0.7;
    }

    .wa-guest-item.wa-sent .wa-guest-name,
    .wa-guest-item.wa-sent .wa-guest-phone {
        color: #94a3b8;
    }

    /* الترقيم */
    .wa-guest-number {
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        line-height: 30px;
        background: #f1f5f9;
        border: 1.5px solid #cbd5e1;
        border-radius: 50%;
        text-align: center;
        font-weight: 700;
        color: #0C4A6E;
        font-size: 13px;
    }

    .wa-guest-item.wa-sent .wa-guest-number {
        background: #d1fae5;
        border-color: #25D366;
        color: #065f46;
    }

    .wa-guest-item.wa-next .wa-guest-number {
        background: #25D366;
        border-color: #25D366;
        color: #fff;
    }

    /* معلومات الضيف */
    .wa-guest-info {
        flex: 1;
        min-width: 0;
    }

    .wa-guest-name {
        font-weight: 700;
        color: #0C4A6E;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .wa-guest-phone {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .wa-guest-seat {
        flex: 0 0 auto;
    }

    .wa-guest-action {
        flex: 0 0 130px;
        text-align: end;
    }

    /* زر الإرسال */
    .wa-send-btn {
        background: #25D366;
        color: #fff;
        font-weight: 600;
        padding: 7px 16px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        white-space: nowrap;
        border: none;
    }

    .wa-send-btn:hover {
        background: #128C7E;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(37, 211, 102, 0.35);
        text-decoration: none;
    }

    /* زر "تم الإرسال" - بلون مختلف */
    .wa-send-btn.wa-sent-btn {
        background: #fff;
        color: #25D366;
        border: 1.5px solid #25D366;
    }

    .wa-send-btn.wa-sent-btn:hover {
        background: #25D366;
        color: #fff;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .wa-guest-item {
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px;
        }

        .wa-guest-info {
            flex: 1 1 60%;
        }

        .wa-guest-seat {
            flex: 0 0 auto;
        }

        .wa-guest-action {
            flex: 1 1 100%;
            text-align: center;
            margin-top: 4px;
        }
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
