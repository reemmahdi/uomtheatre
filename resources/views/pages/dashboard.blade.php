@extends('layouts.app')

@section('content')

@php
    use App\Models\Event;
    use App\Models\Status;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Str;

    $user = Auth::user();
    $roleName = $user->role?->name;

    $baseQuery = Event::query();

    if ($roleName === 'event_manager') {
        $baseQuery->where('created_by', $user->id);
    }

    $statusIds = Status::whereIn('name', ['draft', 'added', 'active', 'published', 'rejected', 'cancelled', 'closed', 'end'])
        ->pluck('id', 'name');

    $stats = [
        'draft'     => (clone $baseQuery)->where('status_id', $statusIds['draft']     ?? 0)->count(),
        'added'     => (clone $baseQuery)->where('status_id', $statusIds['added']     ?? 0)->count(),
        'active'    => (clone $baseQuery)->where('status_id', $statusIds['active']    ?? 0)->count(),
        'published' => (clone $baseQuery)->where('status_id', $statusIds['published'] ?? 0)->count(),
        'total'     => (clone $baseQuery)->count(),
    ];

    $publishedStatusId = $statusIds['published'] ?? 0;

    $currentEvent = (clone $baseQuery)
        ->where('status_id', $publishedStatusId)
        ->where('start_datetime', '<=', now())
        ->where('end_datetime', '>=', now())
        ->orderBy('start_datetime', 'desc')
        ->first();

    if (!$currentEvent) {
        $currentEvent = (clone $baseQuery)
            ->where('status_id', $publishedStatusId)
            ->where('start_datetime', '>=', now())
            ->orderBy('start_datetime', 'asc')
            ->first();
    }

    $attendanceRate = 0;
    $currentEventTitle = null;
    $currentEventStatus = null;
    $checkedInCount = 0;
    $reservedCount = 0;

    if ($currentEvent) {
        $reservedCount = $currentEvent->reservedSeatsCount();
        $checkedInCount = $currentEvent->checkedInCount();
        $attendanceRate = $reservedCount > 0
            ? round(($checkedInCount / $reservedCount) * 100, 1)
            : 0;
        $currentEventTitle = $currentEvent->title;
        $currentEventStatus = $currentEvent->isOngoing() ? 'ongoing' : 'upcoming';
    }

    $roleDescriptions = [
        'super_admin'        => 'مرحباً بك مدير النظام. لديك صلاحيات كاملة على إدارة المستخدمين والفعاليات والصلاحيات.',
        'event_manager'      => 'بصفتك مسؤول الفعاليات في النظام، مهمتك إنشاء الفعاليات وإرسالها لمكتب رئاسة الجامعة للموافقة، ثم نشرها للجمهور وحجز مقاعد الوفود.',
        'theater_manager'    => 'بصفتك مدير المسرح، يمكنك متابعة كل الفعاليات المعتمدة والمنشورة في القاعة.',
        'university_office'  => 'بصفتك مدير مكتب رئاسة الجامعة، مهمتك مراجعة الفعاليات المرسلة من قبل مسؤول الإعلام واتخاذ قرار الموافقة أو الرفض.',
        'receptionist'       => 'بصفتك موظف الاستقبال، مهمتك مسح رموز QR لتذاكر الضيوف وتسجيل حضورهم في الفعاليات المنشورة.',
    ];
    $description = $roleDescriptions[$roleName] ?? 'مرحباً بك في نظام حجز المقاعد.';

    $isOwnEvents = ($roleName === 'event_manager');
@endphp

<div class="container-fluid p-3">

    
    <div class="row g-3 mb-3">

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #64748b;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #64748b;">{{ $stats['draft'] }}</div>
                        <div class="label">
                            {{ $isOwnEvents ? 'مسوداتي' : 'المسودات' }}
                        </div>
                    </div>
                    <div class="icon" style="background: #f1f5f9; color: #64748b;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #f59e0b;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #f59e0b;">{{ $stats['added'] }}</div>
                        <div class="label">
                            الفعاليات المرسلة للرئاسة
                        </div>
                    </div>
                    <div class="icon" style="background: #fef3c7; color: #f59e0b;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #15803D;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #15803D;">{{ $stats['active'] }}</div>
                        <div class="label">
                            الفعاليات النشطة
                            <small class="d-block text-muted fw-normal" style="font-size: 11px; margin-top: 3px;">
                                (جاهزة للنشر)
                            </small>
                        </div>
                    </div>
                    <div class="icon" style="background: #dcfce7; color: #15803D;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-3 mb-3">

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #0C4A6E;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #0C4A6E;">{{ $stats['published'] }}</div>
                        <div class="label">
                            الفعاليات المنشورة
                            <small class="d-block text-muted fw-normal" style="font-size: 11px; margin-top: 3px;">
                                (متاحة للجمهور)
                            </small>
                        </div>
                    </div>
                    <div class="icon" style="background: #dbeafe; color: #0C4A6E;">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #C9A530;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #C9A530;">{{ $stats['total'] }}</div>
                        <div class="label">
                            {{ $isOwnEvents ? 'إجمالي فعالياتي' : 'إجمالي الفعاليات' }}
                            <small class="d-block text-muted fw-normal" style="font-size: 11px; margin-top: 3px;">
                                (جميع الحالات)
                            </small>
                        </div>
                    </div>
                    <div class="icon" style="background: #fef9e7; color: #C9A530;">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="stat-card" style="border-bottom: 4px solid #DC2626;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="number" style="color: #DC2626;">
                            @if($currentEvent)
                                {{ $attendanceRate }}<small style="font-size: 24px;">%</small>
                            @else
                                —
                            @endif
                        </div>
                        <div class="label">
                            نسبة حضور الفعالية الحالية
                            @if($currentEventTitle)
                                <small class="d-block text-muted fw-normal" style="font-size: 11px; margin-top: 3px;">
                                    @if($currentEventStatus === 'ongoing')
                                        <i class="bi bi-broadcast-pin" style="color: #DC2626;"></i> جارية الآن:
                                    @else
                                        <i class="bi bi-calendar-event"></i> القادمة:
                                    @endif
                                    {{ Str::limit($currentEventTitle, 22) }}
                                </small>
                                @if($currentEventStatus === 'ongoing')
                                <small class="d-block text-muted fw-normal" style="font-size: 10px;">
                                    ({{ $checkedInCount }} من {{ $reservedCount }} مسجّل)
                                </small>
                                @endif
                            @else
                                <small class="d-block text-muted fw-normal" style="font-size: 11px; margin-top: 3px;">
                                    (لا توجد فعالية حالية)
                                </small>
                            @endif
                        </div>
                    </div>
                    <div class="icon" style="background: #fef2f2; color: #DC2626;">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    
    <div class="card-custom p-4">
        <div class="d-flex gap-3 align-items-start">
            <div style="background: linear-gradient(135deg, #0C4A6E, #075985); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="bi bi-info-circle-fill" style="color: #fff; font-size: 22px;"></i>
            </div>
            <div>
                <h6 class="mb-2" style="color: #0C4A6E; font-weight: 700;">
                    دورك في النظام
                </h6>
                <p class="mb-0 text-muted" style="line-height: 1.8;">
                    {{ $description }}
                </p>
            </div>
        </div>
    </div>

</div>

@endsection
