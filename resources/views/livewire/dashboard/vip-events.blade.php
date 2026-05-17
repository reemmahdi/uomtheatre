<div>

{{-- ════════════════════════════════════════════════════
     ✨ شريط البحث والترويسة
     ════════════════════════════════════════════════════ --}}
<div class="card-custom p-3 mb-3">
    <div class="row g-3 align-items-center">
        <div class="col-md-6">
            <h6 class="mb-1" style="color: #0C4A6E; font-weight: 700;">
                <i class="bi bi-star-fill" style="color: #C9A530;"></i>
                إدارة حجز مقاعد الوفود
            </h6>
            <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                المقاعد المتاحة للوفود = 52 مقعد ثابت + المقاعد التي تستبعدينها من الجمهور
            </small>
        </div>
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text" style="background: #f8fafc;">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text"
                       wire:model.live.debounce.300ms="searchTitle"
                       class="form-control"
                       placeholder="ابحث عن اسم الفعالية...">
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════
     ✨ بطاقات الفعاليات (Grid)
     ════════════════════════════════════════════════════ --}}
@if($events->count() > 0)

{{-- شريط الإجمالي --}}
<div class="d-flex justify-content-between align-items-center mb-3 px-2">
    <small class="text-muted">
        <i class="bi bi-collection"></i>
        إجمالي الفعاليات: <strong style="color: #0C4A6E;">{{ $events->count() }}</strong>
    </small>
</div>

<div class="row g-3">
    @foreach($events as $event)
    @php
        $totalVip       = $event->total_vip_seats ?? 0;
        $vipFixed       = $event->vip_fixed_count ?? 0;
        $vipExtra       = $event->vip_extra_count ?? 0;
        $available      = $totalVip - $event->vip_booked;
        $isFullyBooked  = $totalVip > 0 && $event->vip_booked >= $totalVip;
        $bookingPct     = $totalVip > 0 ? round(($event->vip_booked / $totalVip) * 100) : 0;

        $statusName  = $event->status?->name ?? 'unknown';
        $statusLabel = match($statusName) {
            'added'     => 'مضافة',
            'active'    => 'نشطة',
            'published' => 'منشورة',
            'rejected'  => 'مرفوضة',
            'closed'    => 'مغلقة',
            'cancelled' => 'ملغاة',
            default     => $statusName,
        };
        $statusColor = match($statusName) {
            'added'     => '#3B82F6',
            'active'    => '#8B5CF6',
            'published' => '#10B981',
            'rejected'  => '#DC2626',
            default     => '#6B7280',
        };
    @endphp

    <div class="col-lg-6 col-xl-4">
        <div class="vip-event-card">

            {{-- ════ الترويسة: العنوان + الحالة ════ --}}
            <div class="card-header-strip" style="background: linear-gradient(135deg, #0C4A6E, #075985);">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <h6 class="card-title-text" title="{{ $event->title }}">
                        {{ $event->title }}
                    </h6>
                    <span class="status-pill" style="background: {{ $statusColor }};">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            {{-- ════ الجسم ════ --}}
            <div class="card-body-content">

                {{-- التواريخ --}}
                <div class="date-row">
                    <div class="date-block">
                        <i class="bi bi-calendar-event" style="color: #0C4A6E;"></i>
                        <div>
                            <small class="text-muted d-block">الانطلاق</small>
                            <strong dir="ltr">{{ $event->start_datetime->format('Y-m-d') }}</strong>
                            <small class="d-block" style="color: #0C4A6E;" dir="ltr">{{ $event->start_datetime->format('h:i A') }}</small>
                        </div>
                    </div>
                    <div class="date-divider"></div>
                    <div class="date-block">
                        <i class="bi bi-calendar-check" style="color: #15803D;"></i>
                        <div>
                            <small class="text-muted d-block">الاختتام</small>
                            <strong dir="ltr">{{ $event->end_datetime->format('Y-m-d') }}</strong>
                            <small class="d-block" style="color: #15803D;" dir="ltr">{{ $event->end_datetime->format('h:i A') }}</small>
                        </div>
                    </div>
                </div>

                {{-- إحصائيات الحجز --}}
                <div class="booking-stats">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">
                            <i class="bi bi-people-fill"></i> الحجوزات
                        </small>
                        <strong style="color: #0C4A6E; font-size: 16px;">
                            {{ $event->vip_booked }} / {{ $totalVip }}
                        </strong>
                    </div>

                    {{-- progress bar --}}
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar"
                             role="progressbar"
                             style="width: {{ $bookingPct }}%; background: {{ $isFullyBooked ? '#DC2626' : 'linear-gradient(90deg, #15803D, #22C55E)' }};">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">
                            <i class="bi bi-bookmark-fill" style="color: #C9A530;"></i>
                            {{ $vipFixed }} ثابت
                            @if($vipExtra > 0)
                                + <span style="color: #15803D;">{{ $vipExtra }} إضافي</span>
                            @endif
                        </small>
                        <small class="{{ $available > 0 ? 'text-success' : 'text-danger' }}">
                            @if($available > 0)
                                <i class="bi bi-check-circle"></i> {{ $available }} متاح
                            @else
                                <i class="bi bi-lock-fill"></i> مكتمل
                            @endif
                        </small>
                    </div>
                </div>

                {{-- زر واحد فقط: إدارة الضيوف --}}
                <div class="card-actions">
                    <a href="{{ route('dashboard.vip-guests', $event->uuid) }}"
                       class="btn btn-vip-action btn-guests-action"
                       title="إدارة قائمة ضيوف الوفود">
                        <i class="bi bi-people-fill"></i>
                        @if($event->vip_booked == 0)
                            إدارة الضيوف
                        @else
                            الضيوف ({{ $event->vip_booked }})
                        @endif
                    </a>
                </div>

            </div>
        </div>
    </div>
    @endforeach
</div>

@else

{{-- لا توجد فعاليات --}}
<div class="card-custom p-5 text-center">
    <i class="bi bi-calendar-x" style="font-size: 60px; color: #0369A1; opacity: 0.4;"></i>
    @if(!empty($searchTitle))
        <p class="mt-3 text-muted">لا توجد فعاليات مطابقة للبحث "{{ $searchTitle }}"</p>
        <button wire:click="$set('searchTitle', '')" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="bi bi-x-circle"></i> مسح البحث
        </button>
    @else
        <p class="mt-3 text-muted fw-bold">لا توجد فعاليات جاهزة لحجز مقاعد الوفود</p>
        <p class="small text-muted">يجب أن تكون الفعالية بحالة "مضافة" أو أعلى</p>
    @endif
</div>

@endif

{{-- ════════════════════════════════════════════════════
     ✨ تنسيقات البطاقات
     ════════════════════════════════════════════════════ --}}
<style>
    /* البطاقة الرئيسية */
    .vip-event-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: all 0.25s ease;
        border: 1px solid #E2E8F0;
    }
    .vip-event-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(12, 74, 110, 0.15);
        border-color: #C9A530;
    }

    /* شريط الترويسة */
    .card-header-strip {
        color: #fff;
        padding: 14px 16px;
        min-height: 70px;
        display: flex;
        align-items: center;
    }
    .card-title-text {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .status-pill {
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 12px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* جسم البطاقة */
    .card-body-content {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* صف التواريخ */
    .date-row {
        display: flex;
        align-items: stretch;
        gap: 12px;
        background: #F8FAFC;
        border-radius: 8px;
        padding: 12px;
    }
    .date-block {
        display: flex;
        gap: 8px;
        align-items: center;
        flex: 1;
    }
    .date-block i {
        font-size: 22px;
        flex-shrink: 0;
    }
    .date-block strong {
        font-size: 13px;
        color: #1f2937;
        display: block;
    }
    .date-divider {
        width: 1px;
        background: #E2E8F0;
    }

    /* إحصائيات الحجز */
    .booking-stats {
        background: #FEFCF3;
        border: 1px solid #FEF3C7;
        border-radius: 8px;
        padding: 12px;
    }
    .booking-stats .progress {
        background: #fff;
        border-radius: 6px;
    }

    /* الأزرار */
    .card-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
    }
    .btn-vip-action {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        text-align: center;
        color: #fff !important;
        text-decoration: none;
        border: 0;
        transition: all 0.2s;
    }
    .btn-vip-action:hover {
        transform: translateY(-1px);
        opacity: 0.95;
    }
    .btn-primary-action {
        background: linear-gradient(135deg, #0C4A6E, #075985);
    }
    .btn-guests-action {
        background: linear-gradient(135deg, #15803D, #166534);
    }
</style>

</div>
