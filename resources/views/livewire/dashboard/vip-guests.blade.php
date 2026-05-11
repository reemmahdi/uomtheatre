<div>

{{-- ✨ عنوان الفعالية --}}
<div class="card-custom p-4 mb-4 event-title-card">
    <div class="d-flex align-items-center gap-3">
        <div class="event-icon-circle">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="flex-grow-1">
            <div class="small mb-1" style="color: rgba(255,255,255,0.75);">قائمة ضيوف الوفود</div>
            <h3 class="mb-0 event-main-title">{{ $event->title }}</h3>
        </div>
        <div>
            <a href="{{ route('dashboard.vip-booking', $event->uuid) }}"
               class="btn btn-sm"
               style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.4); color: #fff; font-weight: 600;">
                <i class="bi bi-grid-3x3-gap"></i> إدارة المقاعد
            </a>
        </div>
    </div>
</div>

{{-- ✨ قائمة الوفود --}}
@if($bookings->count() > 0)
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-people"></i> قائمة الوفود ({{ $bookings->count() }})</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button"
                    class="btn btn-sm"
                    style="background:#25D366;color:#fff;font-weight:700;"
                    data-bs-toggle="modal"
                    data-bs-target="#sendAllWhatsAppModal">
                <i class="bi bi-whatsapp"></i> إرسال للكل
            </button>
        </div>
    </div>

    {{-- ملاحظة واتساب --}}
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
                    <th style="width: 180px;" class="text-center">الإجراءات</th>
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

                            {{-- ✏️ تعديل --}}
                            <button type="button"
                                    wire:click="openEditBooking({{ $booking->id }})"
                                    class="btn-action-small btn-edit-small"
                                    title="تعديل بيانات الضيف">
                                <i class="bi bi-pencil"></i>
                            </button>

                            {{-- ❌ إلغاء --}}
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
{{--  Modal: إرسال دعوات الواتساب الجماعي                           --}}
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
                {{-- شريط التقدم + زر فتح الكل --}}
                <div class="wa-progress-section p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong style="color: #065f46;">
                            <i class="bi bi-check2-circle"></i>
                            التقدم: <span id="wa-sent-count">0</span> من <span id="wa-total-count">{{ $bookings->count() }}</span>
                        </strong>
                        <div class="d-flex gap-2">
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
                    <div id="wa-popup-warning" class="alert alert-warning mt-2 mb-0 py-2 small" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>تنبيه:</strong> المتصفح حجب بعض النوافذ. اضغط على أيقونة
                        <i class="bi bi-shield-exclamation"></i>
                        بشريط العنوان (يمين الرابط) واختر "السماح دائماً للنوافذ المنبثقة من هذا الموقع"، ثم أعد المحاولة.
                        أو استخدم زر "إرسال" الفردي لكل ضيف بالأسفل.
                    </div>
                </div>

                {{-- قائمة الضيوف --}}
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

<script>
(function() {
    const STORAGE_KEY = 'wa_sent_event_{{ $event->id }}';

    function getSentIds() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) { return []; }
    }

    function saveSentIds(ids) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(ids)); } catch (e) {}
    }

    function updateProgress() {
        const sentIds = getSentIds();
        const total = document.querySelectorAll('.wa-guest-item').length;
        const percent = total > 0 ? Math.round((sentIds.length / total) * 100) : 0;
        const sentCountEl = document.getElementById('wa-sent-count');
        const progressBar = document.getElementById('wa-progress-bar');
        if (sentCountEl) sentCountEl.textContent = sentIds.length;
        if (progressBar) progressBar.style.width = percent + '%';
    }

    function applySentState(bookingId) {
        const item = document.querySelector('.wa-guest-item[data-booking-id="' + bookingId + '"]');
        if (!item) return;
        item.classList.add('wa-sent');
        const btn = item.querySelector('.wa-send-btn');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> تم الإرسال';
            btn.classList.add('wa-sent-btn');
        }
    }

    window.bulkOpenAllWhatsApp = function() {
        const btn = document.getElementById('wa-bulk-open-btn');
        const warningEl = document.getElementById('wa-popup-warning');
        if (warningEl) warningEl.style.display = 'none';

        const sentIds = getSentIds();
        const items = document.querySelectorAll('.wa-guest-item');
        const pending = [];

        items.forEach(function(item) {
            const bookingId = parseInt(item.dataset.bookingId);
            if (!sentIds.includes(bookingId)) {
                const link = item.querySelector('.wa-send-btn');
                if (link) pending.push({ id: bookingId, url: link.href });
            }
        });

        if (pending.length === 0) {
            alert('تم إرسال كل الدعوات بالفعل ✓\n\nلإعادة الإرسال، اضغط "إعادة التعيين" أولاً.');
            return;
        }

        if (!confirm('سيتم فتح ' + pending.length + ' تبويب واتساب دفعة واحدة.\n\nملاحظة مهمة: قد يطلب المتصفح "السماح بالنوافذ المنبثقة" أول مرة.\n\nهل تريد المتابعة؟')) {
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جارٍ الفتح...';
        }

        let blockedCount = 0;
        let openedCount = 0;

        pending.forEach(function(entry, index) {
            setTimeout(function() {
                const newWindow = window.open(entry.url, '_blank');

                if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                    blockedCount++;
                } else {
                    openedCount++;
                    const updatedSent = getSentIds();
                    if (!updatedSent.includes(entry.id)) {
                        updatedSent.push(entry.id);
                        saveSentIds(updatedSent);
                    }
                    applySentState(entry.id);
                }

                if (index === pending.length - 1) {
                    setTimeout(function() {
                        updateProgress();
                        scrollToNextUnsent();
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> فتح كل التبويبات تلقائياً';
                        }
                        if (blockedCount > 0) {
                            if (warningEl) warningEl.style.display = 'block';
                            alert('⚠️ المتصفح حجب ' + blockedCount + ' نافذة من أصل ' + pending.length + '.\n\n• تم فتح: ' + openedCount + '\n• محجوب: ' + blockedCount + '\n\nاسمح بالنوافذ المنبثقة من إعدادات الموقع، ثم أعد المحاولة.\nأو استخدم زر "إرسال" الفردي لكل ضيف.');
                        } else {
                            alert('✅ تم فتح ' + openedCount + ' تبويب واتساب بنجاح.\n\nاضغط Enter في كل تبويب لإرسال الرسالة.');
                        }
                    }, 300);
                }
            }, index * 50);
        });
    };

    window.markAsSent = function(linkEl, bookingId) {
        const sentIds = getSentIds();
        if (!sentIds.includes(bookingId)) {
            sentIds.push(bookingId);
            saveSentIds(sentIds);
        }
        setTimeout(function() {
            applySentState(bookingId);
            updateProgress();
            scrollToNextUnsent();
        }, 100);
        return true;
    };

    function scrollToNextUnsent() {
        const items = document.querySelectorAll('.wa-guest-item:not(.wa-sent)');
        if (items.length > 0) {
            items[0].classList.add('wa-next');
            document.querySelectorAll('.wa-guest-item.wa-next').forEach(function(el, i) {
                if (i > 0) el.classList.remove('wa-next');
            });
            items[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    window.resetWhatsAppProgress = function() {
        if (!confirm('هل تريد إعادة تعيين التقدم؟ ستُمسح كل علامات "تم الإرسال" لهذه الفعالية.')) return;
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

@else
{{-- لا يوجد ضيوف محجوزين --}}
<div class="card-custom p-5 text-center mb-4">
    <i class="bi bi-people" style="font-size: 3rem; color: #cbd5e1;"></i>
    <p class="mt-3 text-muted mb-3">لم يتم حجز أي مقعد وفود لهذه الفعالية بعد.</p>
    <a href="{{ route('dashboard.vip-booking', $event->uuid) }}"
       class="btn btn-sm"
       style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; font-weight: 600;">
        <i class="bi bi-grid-3x3-gap"></i> الذهاب إلى إدارة المقاعد
    </a>
</div>
@endif

{{-- زر الرجوع --}}
<div class="text-center mt-3">
    <a href="{{ route('dashboard.vip-events') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right"></i> الرجوع للفعاليات
    </a>
</div>

{{-- نافذة تعديل بيانات الضيف --}}
<div class="modal fade" id="editBookingModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square" style="color: #0C4A6E;"></i> تعديل بيانات الضيف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @error('editGuestName')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editGuestPhone')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label fw-bold">اسم الضيف <span class="text-danger">*</span></label>
                    <input type="text" wire:model="editGuestName" class="form-control" placeholder="الاسم الكامل للضيف">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">رقم الجوال <span class="text-danger">*</span></label>
                    <input type="text" wire:model="editGuestPhone" class="form-control" dir="ltr" placeholder="07701234567">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="updateBooking" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateBooking"><i class="bi bi-check-lg"></i> حفظ التعديلات</span>
                    <span wire:loading wire:target="updateBooking"><span class="wire-loading"></span> جاري الحفظ...</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- التنسيقات --}}
<style>
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

    .event-main-title {
        color: #fff;
        font-weight: 800;
        font-size: 24px;
        font-family: 'Cairo', 'Tajawal', sans-serif;
        line-height: 1.4;
    }

    .vip-guests-table th {
        font-weight: 700;
        color: #374151;
        font-size: 13px;
        padding: 12px 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .vip-guests-table td {
        padding: 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
    }

    .phone-number {
        font-family: monospace;
        font-size: 13px;
        background: #f1f5f9;
        padding: 3px 8px;
        border-radius: 6px;
        color: #374151;
    }

    .btn-action-small {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-whatsapp-small {
        background: #dcfce7;
        color: #15803d;
    }
    .btn-whatsapp-small:hover {
        background: #25D366;
        color: #fff;
    }

    .btn-edit-small {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .btn-edit-small:hover {
        background: #1d4ed8;
        color: #fff;
    }

    .btn-delete-small {
        background: #fee2e2;
        color: #dc2626;
    }
    .btn-delete-small:hover {
        background: #dc2626;
        color: #fff;
    }

    /* ════ واتساب الجماعي ════ */
    .wa-progress-section {
        background: #f0fdf4;
    }

    .wa-guests-list {
        padding: 8px 0;
    }

    .wa-guest-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s;
    }

    .wa-guest-item:hover {
        background: #f9fafb;
    }

    .wa-guest-item.wa-sent {
        background: #f0fdf4;
        opacity: 0.75;
    }

    .wa-guest-item.wa-next {
        background: #fffbeb;
        border-right: 3px solid #f59e0b;
    }

    .wa-guest-number {
        width: 28px;
        height: 28px;
        background: #0C4A6E;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .wa-guest-info {
        flex: 1;
    }

    .wa-guest-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
    }

    .wa-guest-phone {
        font-size: 12px;
        color: #64748b;
        font-family: monospace;
    }

    .wa-guest-seat {
        flex-shrink: 0;
    }

    .wa-guest-action {
        flex-shrink: 0;
    }

    .wa-send-btn {
        background: #25D366;
        color: #fff;
        font-weight: 600;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
    }

    .wa-send-btn:hover {
        background: #128C7E;
        color: #fff;
    }

    .wa-sent-btn {
        background: #6b7280 !important;
    }
</style>

</div>
