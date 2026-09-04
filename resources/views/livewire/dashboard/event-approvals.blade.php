

<div>

<div class="card-custom p-3 mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-7">
            <h6 class="mb-1" style="color: var(--primary);">
                <i class="bi bi-clipboard-check-fill" style="color: #15803D;"></i>
                الفعاليات بانتظار موافقتي
            </h6>
            <small class="text-muted">
                دورك الحالي: <strong style="color: #0C4A6E;">{{ $stats['role_label'] }}</strong>
            </small>
        </div>
        <div class="col-md-5 text-end">
            <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 10px 18px; font-size: 14px; font-weight: 700;">
                <i class="bi bi-hourglass-split"></i>
                {{ $stats['pending_count'] }} فعالية بانتظار قرارك
            </span>
        </div>
    </div>
</div>

@if($events->count() > 0)
<div class="card-custom p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background: #f1f5f9;">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>اسم الفعالية</th>
                    <th style="width: 200px;">موعد الانطلاق</th>
                    <th style="width: 280px; text-align: center;">الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                @php
                    $startTime = $event->start_datetime->format('h:i');
                    $period = $event->start_datetime->format('A') === 'AM' ? 'صباحاً' : 'مساءً';
@endphp
                <tr>
                    <td><strong style="color: #0C4A6E;">{{ $loop->iteration }}</strong></td>
                    <td>
                        <strong style="color: #0C4A6E; font-size: 15px;">{{ $event->title }}</strong>
                    </td>
                    <td>
                        <small class="text-muted d-block" dir="ltr">
                            <i class="bi bi-calendar3" style="color: #0C4A6E;"></i>
                            {{ $event->start_datetime->format('Y-m-d') }}
                        </small>
                        <small class="fw-bold" style="color: #0C4A6E;">
                            <i class="bi bi-clock"></i>
                            {{ $startTime }} {{ $period }}
                        </small>
                    </td>
                    <td style="text-align: center;">
                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                            
                            <button type="button"
                                    wire:click="openDetailsModal({{ $event->id }})"
                                    class="btn btn-sm"
                                    style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; font-weight: 600; padding: 6px 12px;"
                                    title="عرض التفاصيل الكاملة">
                                <i class="bi bi-eye-fill"></i> تفاصيل
                            </button>

                            
                            <button type="button"
                                    wire:click="requestApprove({{ $event->id }})"
                                    class="btn btn-sm"
                                    style="background: linear-gradient(135deg, #15803D, #166534); color: #fff; font-weight: 600; padding: 6px 12px;">
                                <i class="bi bi-check-circle-fill"></i> موافقة
                            </button>

                            
                            <button type="button"
                                    wire:click="openRejectModal({{ $event->id }})"
                                    class="btn btn-sm btn-outline-danger"
                                    style="font-weight: 600; padding: 6px 12px;">
                                <i class="bi bi-x-circle-fill"></i> رفض
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
<div class="card-custom p-5 text-center">
    <i class="bi bi-check2-all" style="font-size: 70px; color: #15803D;"></i>
    <h4 class="mt-3" style="color: #15803D;">لا توجد فعاليات بانتظار قرارك</h4>
    <p class="text-muted">عندما يرسل مدير الإعلام فعالية للموافقة، ستظهر هنا.</p>
</div>
@endif

<div class="modal fade" id="eventDetailsModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @if($viewingEvent)
            <div class="modal-header" style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff;">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle-fill"></i> تفاصيل الفعالية
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="closeDetailsModal"></button>
            </div>
            <div class="modal-body">

                <h4 style="color: #0C4A6E; margin-bottom: 12px;">
                    <i class="bi bi-calendar-event"></i> {{ $viewingEvent->title }}
                </h4>

                @if($viewingEvent->description)
                <div class="mb-3 p-3" style="background: #f8fafc; border-radius: 8px; border-right: 3px solid #0C4A6E;">
                    <small class="text-muted d-block mb-1"><strong>الوصف:</strong></small>
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $viewingEvent->description }}</p>
                </div>
                @endif

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="p-3" style="background: #f0f9ff; border-radius: 8px;">
                            <small class="text-muted d-block">
                                <i class="bi bi-calendar3"></i> <strong>البداية</strong>
                            </small>
                            <div style="color: #0C4A6E; font-weight: 600;" dir="ltr">
                                {{ $viewingEvent->start_datetime->format('Y-m-d') }}
                            </div>
                            <div style="color: #0C4A6E;">
                                {{ $viewingEvent->start_datetime->format('h:i') }}
                                {{ $viewingEvent->start_datetime->format('A') === 'AM' ? 'صباحاً' : 'مساءً' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="background: #fef3c7; border-radius: 8px;">
                            <small class="text-muted d-block">
                                <i class="bi bi-calendar3"></i> <strong>النهاية</strong>
                            </small>
                            <div style="color: #92400e; font-weight: 600;" dir="ltr">
                                {{ $viewingEvent->end_datetime->format('Y-m-d') }}
                            </div>
                            <div style="color: #92400e;">
                                {{ $viewingEvent->end_datetime->format('h:i') }}
                                {{ $viewingEvent->end_datetime->format('A') === 'AM' ? 'صباحاً' : 'مساءً' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1"><strong>أنشأها:</strong></small>
                        <span>
                            <i class="bi bi-person-circle" style="color: #0C4A6E;"></i>
                            {{ $viewingEvent->creator->name ?? '—' }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1"><strong>تاريخ الإنشاء:</strong></small>
                        <span dir="ltr">
                            <i class="bi bi-clock-history" style="color: #0C4A6E;"></i>
                            {{ $viewingEvent->created_at->format('Y-m-d H:i') }}
                        </span>
                    </div>
                </div>

                
                @php
                    $currentRound = $viewingEvent->currentRound();
                    $previousApprovals = $viewingEvent->approvals
                        ->where('status', 'rejected')
                        ->sortBy('round_number');
@endphp

                @if($previousApprovals->count() > 0)
                <div class="alert alert-warning mt-3" style="background: #fef3c7; border-color: #f59e0b; color: #92400e;">
                    <h6 class="alert-heading mb-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        تنبيه: هذه الفعالية أُعيد إرسالها {{ $previousApprovals->count() }} مرة
                    </h6>

                    <small class="d-block mb-2">سجل القرارات السابقة:</small>

                    @foreach($previousApprovals as $approval)
                    <div class="mt-2 p-2" style="background: rgba(255,255,255,0.5); border-radius: 6px;">
                        <strong>
                            <i class="bi bi-x-circle-fill"></i>
                            الدورة #{{ $approval->round_number }}: رُفضت
                        </strong>
                        <small class="text-muted d-block mt-1" dir="ltr">
                            {{ $approval->created_at->format('Y-m-d H:i') }}
                        </small>
                        @if($approval->rejection_reason)
                        <div class="mt-2 p-2" style="background: #ffffff; border-radius: 4px; border-right: 3px solid #DC2626;">
                            <small class="text-muted"><strong>السبب:</strong></small>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $approval->rejection_reason }}</p>
                        </div>
                        @else
                        <small class="text-muted fst-italic">لم يُكتب سبب الرفض</small>
                        @endif
                    </div>
                    @endforeach

                    <hr class="my-3" style="border-color: #f59e0b;">
                    <small class="d-block">
                        <i class="bi bi-info-circle"></i>
                        أنتِ الحين تنظرين في <strong>الدورة #{{ $currentRound }}</strong> بعد التعديل
                    </small>
                </div>
                @else
                <div class="alert alert-info mt-3" style="background: #dbeafe; border-color: #0C4A6E; color: #1e40af;">
                    <i class="bi bi-info-circle-fill"></i>
                    هذه أول مرة تُرسل فيها الفعالية للموافقة (الدورة الأولى)
                </div>
                @endif

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeDetailsModal">
                    <i class="bi bi-x"></i> إغلاق
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="rejectApprovalModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #DC2626, #991B1B); color: #fff;">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle-fill"></i> رفض الفعالية
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="cancelReject"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" style="background: #fef3c7; border-color: #f59e0b; color: #92400e;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    سيتم إعادة الفعالية <strong>"{{ $rejectingEventTitle }}"</strong> إلى مدير الإعلام للتعديل.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">سبب الرفض <small class="text-muted">(اختياري)</small></label>

                    
                    <textarea wire:model.live="rejectionNote"
                              class="form-control"
                              rows="4"
                              placeholder="اكتبي سبب الرفض ليتمكن مدير الإعلام من تعديل الفعالية (اختياري)..."
                              maxlength="500"></textarea>
                    @error('rejectionNote')
                        <small class="text-danger mt-1 d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                    <small class="text-muted">
                        {{ mb_strlen($rejectionNote) }} / 500 حرف
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancelReject">
                    <i class="bi bi-x"></i> إلغاء
                </button>
                <button type="button" wire:click="submitReject" class="btn btn-danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submitReject">
                        <i class="bi bi-x-circle-fill"></i> تأكيد الرفض
                    </span>
                    <span wire:loading wire:target="submitReject">
                        <span class="spinner-border spinner-border-sm"></span> جارٍ الإرسال...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:initialized', () => {

    Livewire.on('open-modal', (event) => {
        const modalId = event?.id || event?.[0]?.id || event?.[0];
        if (!modalId) return;

        const el = document.getElementById(modalId);
        if (!el) {
            console.warn('Modal not found:', modalId);
            return;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    });

    Livewire.on('close-modal', () => {
        document.querySelectorAll('.modal.show').forEach(el => {
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        });
    });

});
</script>

</div>

