<div>

<div class="card-custom p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0" style="color: var(--primary);"><i class="bi bi-star-fill" style="color: #E4C05E;"></i> مقاعد الوفود</h6>
            <small class="text-muted">اختر الفعالية لحجز مقاعد الوفود ({{ $totalVipSeats }} مقعد لكل فعالية)</small>
        </div>
        <span class="badge" style="background: linear-gradient(135deg, #E4C05E, #C9A445); color: #5a4500; font-size: 14px; padding: 8px 16px; font-weight: 700;">
            {{ $events->count() }} فعالية
        </span>
    </div>
</div>

@if($events->count() > 0)
<div class="row g-4">
    @foreach($events as $event)
    @php
        $statusColors = ['added'=>'#0369A1','under_review'=>'#E4C05E','active'=>'#075985','published'=>'#0C4A6E','closed'=>'#DC2626'];
        $statusNames = ['added'=>'مضافة','under_review'=>'قيد المراجعة','active'=>'نشطة','published'=>'منشورة','closed'=>'مغلقة'];
        $sName = $event->status->name;
        $sColor = $statusColors[$sName] ?? '#6B7280';
        $sLabel = $statusNames[$sName] ?? $sName;
        $percentage = $totalVipSeats > 0 ? round(($event->vip_booked / $totalVipSeats) * 100) : 0;
    @endphp
    <div class="col-md-6 col-lg-4">
        <div class="card-custom p-4 h-100" style="border-top: 4px solid {{ $sColor }};">
            {{-- العنوان + الحالة --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0" style="color: var(--primary); font-weight: 700;">{{ $event->title }}</h6>
                <span class="badge-role" style="background:{{ $sColor }}20;color:{{ $sColor }};border:1px solid {{ $sColor }}40; white-space: nowrap;">
                    {{ $sLabel }}
                </span>
            </div>

            {{-- التاريخ والوقت --}}
            <div class="mb-3">
                {{-- تاريخ ووقت البدء --}}
                <div class="text-muted small mb-1">
                    <i class="bi bi-calendar-event text-primary"></i>
                    البدء: {{ $event->start_datetime->format('Y-m-d H:i') }}
                </div>
                {{-- تاريخ ووقت الانتهاء --}}
                <div class="text-muted small mb-1">
                    <i class="bi bi-calendar-check text-success"></i>
                    الانتهاء: {{ $event->end_datetime->format('Y-m-d H:i') }}
                </div>
                <div class="text-muted small">
                    <i class="bi bi-person"></i> أنشأها: {{ $event->creator->name }}
                </div>
            </div>

            {{-- إحصائيات الوفود --}}
            <div class="p-3 rounded mb-3" style="background: linear-gradient(135deg, rgba(228, 192, 94, 0.12), rgba(228, 192, 94, 0.05));">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">مقاعد الوفود المحجوزة</span>
                    <strong style="color: #8a6d1a;">{{ $event->vip_booked }} / {{ $totalVipSeats }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" style="width: {{ $percentage }}%; background: linear-gradient(135deg, #E4C05E, #C9A445);"></div>
                </div>
                <div class="text-end mt-1">
                    <small class="text-muted">{{ $percentage }}%</small>
                </div>
            </div>

            {{-- زر حجز الوفود --}}
            <a href="{{ route('dashboard.vip-booking', $event->id) }}" class="btn btn-primary w-100">
                <i class="bi bi-star-fill"></i>
                @if($event->vip_booked == 0)
                    بدء حجز الوفود
                @elseif($event->vip_booked >= $totalVipSeats)
                    عرض قائمة الوفود (مكتمل)
                @else
                    إدارة حجز الوفود ({{ $totalVipSeats - $event->vip_booked }} متاح)
                @endif
            </a>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card-custom p-5 text-center">
    <i class="bi bi-calendar-x" style="font-size: 50px; color: #0369A1;"></i>
    <p class="mt-3 text-muted">لا توجد فعاليات جاهزة لحجز مقاعد الوفود</p>
    <p class="small text-muted">يجب أن تكون الفعالية في حالة "مضافة" أو أعلى</p>
</div>
@endif

</div>
