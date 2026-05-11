
<div>

{{-- ✨ شريط الإحصائيات --}}
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

{{-- ✨ قائمة الفعاليات --}}
@if($approvals->count() > 0)
<div class="card-custom p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background: #f1f5f9;">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>اسم الفعالية</th>
                    <th style="width: 180px;">الوصف</th>
                    <th style="width: 150px;">موعد الانطلاق</th>
                    <th style="width: 130px;">أنشأها</th>
                    <th style="width: 200px; text-align: center;">الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvals as $approval)
                @php
                    $event = $approval->event;
                    $startTime = $event->start_datetime->format('h:i');
                    $period = $event->start_datetime->format('A') === 'AM' ? 'صباحاً' : 'مساءً';
                @endphp
                <tr>
                    <td><strong style="color: #0C4A6E;">{{ $loop->iteration }}</strong></td>
                    <td>
                        <strong style="color: #0C4A6E; font-size: 15px;">{{ $event->title }}</strong>
                        @if($roleName === 'super_admin')
                        <br>
                        <small class="badge" style="background: #fef3c7; color: #8a6d1a; margin-top: 4px;">
                            <i class="bi bi-person-badge"></i>
                            بانتظار: {{ $approval->role->display_name }}
                        </small>
                        @endif
                    </td>
                    <td>
                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($event->description ?? '—', 60) }}</small>
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
                    <td>
                        <small class="text-muted">
                            <i class="bi bi-person-circle"></i>
                            {{ $event->creator->name ?? '—' }}
                        </small>
                    </td>
                    <td style="text-align: center;">
                        <div class="d-flex gap-2 justify-content-center">
                            {{-- زر الموافقة --}}
                            <button type="button"
                                    wire:click="requestApprove({{ $approval->id }})"
                                    class="btn btn-sm"
                                    style="background: linear-gradient(135deg, #15803D, #166534); color: #fff; font-weight: 600; padding: 6px 14px;">
                                <i class="bi bi-check-circle-fill"></i> موافقة
                            </button>

                            {{-- زر الرفض --}}
                            <button type="button"
                                    wire:click="openRejectModal({{ $approval->id }})"
                                    class="btn btn-sm btn-outline-danger"
                                    style="font-weight: 600; padding: 6px 14px;">
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

{{-- ✨ نافذة الرفض --}}
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
                    <br>
                    <small class="mt-2 d-block">
                        <i class="bi bi-info-circle"></i>
                        الموافقات الأخرى (إن وُجدت) ستبقى محفوظة.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">سبب الرفض <span class="text-danger">*</span></label>
                    <textarea wire:model="rejectionNote"
                              class="form-control"
                              rows="4"
                              placeholder="اكتبي سبب الرفض ليتمكن مدير الإعلام من تعديل الفعالية..."
                              maxlength="500"></textarea>
                    @error('rejectionNote')
                        <small class="text-danger mt-1 d-block">
                            <i class="bi bi-exclamation-circle"></i> {{ $message }}
                        </small>
                    @enderror
                    <small class="text-muted">
                        <span x-data x-text="$wire.rejectionNote.length">0</span> / 500 حرف
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

</div>
