<div>


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


@if($bookings->count() > 0)
<div class="card-custom p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-people"></i> قائمة الوفود ({{ $bookings->count() }})</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            
            <a href="{{ route('dashboard.vip-guests.print-list', $event->uuid) }}"
               target="_blank"
               class="btn btn-sm"
               style="background:#0C4A6E;color:#fff;font-weight:700;">
                <i class="bi bi-printer-fill"></i> طباعة القائمة
            </a>

            
            <a href="{{ route('dashboard.vip-guests.print-stickers', $event->uuid) }}"
               target="_blank"
               class="btn btn-sm"
               style="background:#C9A530;color:#fff;font-weight:700;">
                <i class="bi bi-tags-fill"></i> طباعة الملصقات
            </a>

            <div class="form-check form-switch d-inline-flex align-items-center gap-2 ms-2 mb-0"
                 title="عند التفعيل يظهر زر إرسال SMS لكل ضيف">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="smsSwitch" wire:click="toggleSms"
                       @if($event->sms_enabled) checked @endif>
                <label class="form-check-label small fw-bold" for="smsSwitch"
                       style="color:#0C4A6E; cursor:pointer;">
                    رسائل SMS
                    @if($event->sms_enabled)
                        <span class="badge bg-success">مفعلة</span>
                    @else
                        <span class="badge bg-secondary">غير مفعلة</span>
                    @endif
                </label>
            </div>
        </div>
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
                            
                            @if($event->sms_enabled)
                            <button type="button"
                                    class="btn-action-small"
                                    style="background:#0C4A6E;color:#fff;"
                                    wire:click="sendSms({{ $booking->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="sendSms({{ $booking->id }})"
                                    title="إرسال دعوة SMS لـ {{ $booking->guest_name }}">
                                <i class="bi bi-chat-dots-fill"></i>
                            </button>
                            @endif

                            
                            <button type="button"
                                    wire:click="openEditBooking({{ $booking->id }})"
                                    class="btn-action-small btn-edit-small"
                                    title="تعديل بيانات الضيف">
                                <i class="bi bi-pencil"></i>
                            </button>

                            
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






@else

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


<div class="text-center mt-3">
    <a href="{{ route('dashboard.vip-events') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-right"></i> الرجوع للفعاليات
    </a>
</div>


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


<script>
document.addEventListener('livewire:initialized', () => {

    // ✨ فتح modal عند استلام 'open-modal'
    Livewire.on('open-modal', (event) => {
        const modalId = event?.id || event?.[0]?.id || event?.[0];
        if (!modalId) return;
        const el = document.getElementById(modalId);
        if (!el) {
            console.warn('Modal not found:', modalId);
            return;
        }
        // ننتظر قليلاً لو في modal آخر يُغلق بنفس الوقت
        setTimeout(() => {
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();
        }, 200);
    });

    // ✨ إغلاق كل المودالات المفتوحة
    Livewire.on('close-modal', () => {
        document.querySelectorAll('.modal.show').forEach(el => {
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        });
    });

});
</script>

</div>