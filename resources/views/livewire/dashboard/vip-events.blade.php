<div>

{{-- ✨ شريط البحث --}}
<div class="card-custom p-3 mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-6">
            <h6 class="mb-1" style="color: var(--primary);">
                <i class="bi bi-star-fill" style="color: #0C4A6E;"></i>
                إدارة حجز مقاعد الوفود
            </h6>
            <small class="text-muted">{{ $totalVipSeats }} مقعد لكل فعالية</small>
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

{{-- ✨ جدول الفعاليات (مختصر) --}}
@if($events->count() > 0)
<div class="card-custom p-0">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
        <span class="text-muted small">
            <i class="bi bi-info-circle"></i>
            إجمالي الفعاليات: <strong style="color: #0C4A6E;">{{ $events->count() }}</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background: #f1f5f9;">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>اسم الفعالية</th>
                    <th style="width: 150px;">موعد الانطلاق</th>
                    <th style="width: 150px;">موعد الاختتام</th>
                    <th style="width: 160px; text-align: center;">عدد المقاعد المحجوزة</th>
                    <th style="width: 160px; text-align: center;">الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                @php
                    $available = $totalVipSeats - $event->vip_booked;
                    $isFullyBooked = $event->vip_booked >= $totalVipSeats;
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
                        <small class="fw-bold" style="color: #0C4A6E;" dir="ltr">
                            <i class="bi bi-clock"></i>
                            {{ $event->start_datetime->format('H:i') }}
                        </small>
                    </td>
                    <td>
                        <small class="text-muted d-block" dir="ltr">
                            <i class="bi bi-calendar3" style="color: #15803D;"></i>
                            {{ $event->end_datetime->format('Y-m-d') }}
                        </small>
                        <small class="fw-bold" style="color: #15803D;" dir="ltr">
                            <i class="bi bi-clock"></i>
                            {{ $event->end_datetime->format('H:i') }}
                        </small>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge" style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; padding: 8px 16px; font-size: 14px; font-weight: 700;">
                            {{ $event->vip_booked }} / {{ $totalVipSeats }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="{{ route('dashboard.vip-booking', $event->uuid) }}"
                               class="btn btn-sm"
                               style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; font-weight: 600; padding: 6px 14px;"
                               title="إدارة مقاعد الوفود">
                                @if($event->vip_booked == 0)
                                    <i class="bi bi-plus-circle"></i> بدء الحجز
                                @else
                                    <i class="bi bi-grid-3x3-gap"></i> إدارة المقاعد
                                @endif
                            </a>
                            @if($event->vip_booked > 0)
                            <a href="{{ route('dashboard.vip-guests', $event->uuid) }}"
                               class="btn btn-sm"
                               style="background: linear-gradient(135deg, #15803D, #166534); color: #fff; font-weight: 600; padding: 6px 14px;"
                               title="إدارة قائمة الضيوف">
                                <i class="bi bi-people-fill"></i> الضيوف
                            </a>
                            @endif
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
    <i class="bi bi-calendar-x" style="font-size: 50px; color: #0369A1;"></i>
    @if(!empty($searchTitle))
        <p class="mt-3 text-muted">لا توجد فعاليات مطابقة للبحث "{{ $searchTitle }}"</p>
        <button wire:click="$set('searchTitle', '')" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="bi bi-x-circle"></i> مسح البحث
        </button>
    @else
        <p class="mt-3 text-muted">لا توجد فعاليات جاهزة لحجز مقاعد الوفود</p>
        <p class="small text-muted">يجب أن تكون الفعالية في حالة "مضافة" أو أعلى</p>
    @endif
</div>
@endif

</div>
