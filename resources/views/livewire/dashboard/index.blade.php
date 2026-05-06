<div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- ══════════════════════════════════════════════ --}}
{{--             👑 مدير النظام super_admin         --}}
{{-- ══════════════════════════════════════════════ --}}
@if($roleName === 'super_admin')
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom: 3px solid #0C4A6E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color: #0C4A6E;">{{ $totalUsers }}</div>
                    <div class="label">إجمالي المستخدمين</div>
                </div>
                <div class="icon" style="background:rgba(12, 74, 110, 0.1);color:#0C4A6E;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom: 3px solid #075985;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color: #075985;">{{ $activeUsers }}</div>
                    <div class="label">حسابات فعالة</div>
                </div>
                <div class="icon" style="background:rgba(7, 89, 133, 0.12);color:#075985;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom: 3px solid #DC2626;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color: #DC2626;">{{ $inactiveUsers }}</div>
                    <div class="label">حسابات معطّلة</div>
                </div>
                <div class="icon" style="background:rgba(220, 38, 38, 0.1);color:#DC2626;">
                    <i class="bi bi-person-x-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom: 3px solid #E4C05E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color: #8a6d1a;">{{ $totalRoles }}</div>
                    <div class="label">الأدوار</div>
                </div>
                <div class="icon" style="background:rgba(228, 192, 94, 0.2);color:#8a6d1a;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card-custom p-4">
            <h6 class="mb-3"><i class="bi bi-pie-chart-fill"></i> توزيع الأدوار</h6>
            <table class="table table-hover mb-0">
                <thead><tr><th>الدور</th><th>العدد</th><th>النسبة</th></tr></thead>
                <tbody>
                @foreach($rolesDistribution as $role)
                <tr>
                    <td><span class="badge-role" style="background:{{ $role['color'] }}15;color:{{ $role['color'] }};border:1px solid {{ $role['color'] }}30;">{{ $role['display_name'] }}</span></td>
                    <td><strong>{{ $role['count'] }}</strong></td>
                    <td>
                        <div class="progress" style="height:8px;width:120px;background:#F0F9FF;">
                            <div class="progress-bar" style="width:{{ $totalUsers>0?($role['count']/$totalUsers)*100:0 }}%;background: linear-gradient(90deg, #0C4A6E, #0369A1);"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-4">
            <h6 class="mb-3"><i class="bi bi-info-circle-fill"></i> معلومات المسرح</h6>
            <ul class="list-unstyled">
                <li class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">إجمالي المقاعد</span>
                    <strong style="color:#0C4A6E;">{{ config('theatre.total_seats') }}</strong>
                </li>
                <li class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">مقاعد الوفود</span>
                    <strong style="color:#8a6d1a;">{{ config('theatre.vip_seats') }}</strong>
                </li>
                <li class="mb-3 d-flex justify-content-between">
                    <span class="text-muted">أقسام المسرح</span>
                    <strong style="color:#075985;">{{ config('theatre.sections') }}</strong>
                </li>
                <li class="d-flex justify-content-between">
                    <span class="text-muted">حالات الفعاليات</span>
                    <strong style="color:#0369A1;">{{ config('theatre.statuses') }}</strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> آخر المستخدمين</h6>
        <a href="{{ route('dashboard.users') }}" class="btn btn-sm btn-primary">عرض الكل</a>
    </div>
    <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th></tr></thead>
        <tbody>
        @foreach($recentUsers as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td><strong>{{ $user->name }}</strong></td>
            <td>{{ $user->email }}</td>
            <td><span class="badge bg-primary">{{ $user->role->display_name }}</span></td>
            <td>
                @if($user->is_active)
                <span class="badge" style="background: linear-gradient(135deg, #0C4A6E, #0369A1);">فعال</span>
                @else
                <span class="badge bg-danger">معطّل</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--        🎭 مدير المسرح theater_manager          --}}
{{-- ══════════════════════════════════════════════ --}}
@elseif($roleName === 'theater_manager')
<div class="row g-4 mb-4">
    {{-- 1. كل الفعاليات --}}
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #0C4A6E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0C4A6E;">{{ $totalEvents }}</div>
                    <div class="label">كل الفعاليات</div>
                </div>
                <div class="icon" style="background:rgba(12, 74, 110, 0.1);color:#0C4A6E;">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. الفعاليات المنشورة --}}
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #0369A1;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0369A1;">{{ $publishedEvents }}</div>
                    <div class="label">الفعاليات المنشورة</div>
                </div>
                <div class="icon" style="background:rgba(3, 105, 161, 0.12);color:#0369A1;">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. الفعاليات المسوّدة --}}
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #94A3B8;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#475569;">{{ $draftEvents }}</div>
                    <div class="label">الفعاليات المسوّدة</div>
                </div>
                <div class="icon" style="background:rgba(148, 163, 184, 0.18);color:#475569;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. الفعاليات الملغاة (المحذوفة) --}}
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #B91C1C;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#B91C1C;">{{ $cancelledEvents }}</div>
                    <div class="label">الفعاليات الملغاة</div>
                </div>
                <div class="icon" style="background:rgba(185, 28, 28, 0.1);color:#B91C1C;">
                    <i class="bi bi-x-octagon-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-4 mt-4">
    <h6><i class="bi bi-info-circle"></i> دورك</h6>
    <p class="text-muted mb-0">بصفتك مدير قاعة الدكتور محمود الجليلي، مهمتك إنشاء الفعاليات والتأكد من صحة بياناتها، ثم إحالتها إلى مدير الإعلام. وتبقى إمكانية التعديل متاحة ما دامت الفعالية في حالة "مسودة".</p>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--        📢 مدير الإعلام event_manager           --}}
{{-- ══════════════════════════════════════════════ --}}
@elseif($roleName === 'event_manager')
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #0C4A6E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0C4A6E;">{{ $totalEvents }}</div>
                    <div class="label">إجمالي الفعاليات</div>
                </div>
                <div class="icon" style="background:rgba(12, 74, 110, 0.1);color:#0C4A6E;">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #E4C05E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#8a6d1a;">{{ $pendingReview }}</div>
                    <div class="label">بانتظار المراجعة</div>
                </div>
                <div class="icon" style="background:rgba(228, 192, 94, 0.2);color:#8a6d1a;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #075985;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#075985;">{{ $publishedEvents }}</div>
                    <div class="label">منشورة</div>
                </div>
                <div class="icon" style="background:rgba(7, 89, 133, 0.12);color:#075985;">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #E4C05E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#8a6d1a;">{{ config('theatre.vip_seats') }}</div>
                    <div class="label">مقاعد الوفود</div>
                </div>
                <div class="icon" style="background:rgba(228, 192, 94, 0.25);color:#8a6d1a;">
                    <i class="bi bi-star-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-4 mt-4">
    <h6><i class="bi bi-info-circle"></i> دورك</h6>
    <p class="text-muted mb-0">بصفتك مدير الإعلام ومسؤول الفعاليات في النظام، مهمتك تدقيق الفعاليات المرسلة من قبل مدير المسرح، ثم حجز مقاعد الوفود ونشر الفعاليات للجمهور بعد إتمام عملية الحجز.</p>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--         📋 موظف الاستقبال receptionist         --}}
{{-- ══════════════════════════════════════════════ --}}
@elseif($roleName === 'receptionist')
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stat-card" style="border-bottom:3px solid #0C4A6E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0C4A6E;">{{ $checkedInToday }}</div>
                    <div class="label">تم تسجيل حضورهم اليوم</div>
                </div>
                <div class="icon" style="background:rgba(12, 74, 110, 0.1);color:#0C4A6E;">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card" style="border-bottom:3px solid #075985;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#075985;">{{ $totalReservations }}</div>
                    <div class="label">إجمالي الحجوزات</div>
                </div>
                <div class="icon" style="background:rgba(7, 89, 133, 0.12);color:#075985;">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-5 text-center">
    <i class="bi bi-qr-code-scan" style="font-size:70px;color:#0C4A6E;"></i>
    <h4 class="mt-3" style="color:#0C4A6E;">جاهز لتسجيل الحضور</h4>
    <p class="text-muted">امسح رمز QR من التذكرة</p>
    <a href="{{ route('dashboard.checkin') }}" class="btn btn-primary btn-lg mt-2">
        <i class="bi bi-qr-code-scan"></i> بدء تسجيل الحضور
    </a>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--     📊 مكتب الرئيس university_office           --}}
{{-- ══════════════════════════════════════════════ --}}
@elseif($roleName === 'university_office')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom:3px solid #0C4A6E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0C4A6E;">{{ $totalEvents }}</div>
                    <div class="label">إجمالي الفعاليات</div>
                </div>
                <div class="icon" style="background:rgba(12, 74, 110, 0.1);color:#0C4A6E;">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom:3px solid #E4C05E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#8a6d1a;">{{ $totalReservations }}</div>
                    <div class="label">إجمالي الحجوزات</div>
                </div>
                <div class="icon" style="background:rgba(228, 192, 94, 0.2);color:#8a6d1a;">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom:3px solid #075985;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#075985;">{{ $totalCheckedIn }}</div>
                    <div class="label">إجمالي الحضور</div>
                </div>
                <div class="icon" style="background:rgba(7, 89, 133, 0.12);color:#075985;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <h6><i class="bi bi-bar-chart-line"></i> ملخص النظام</h6>
    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, rgba(12, 74, 110, 0.08), rgba(3, 105, 161, 0.05));">
                <span class="text-muted">إجمالي المقاعد</span>
                <h3 style="color:#0C4A6E;">{{ config('theatre.total_seats') }}</h3>
                <small class="text-muted">منها {{ config('theatre.vip_seats') }} مقعد وفود</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, rgba(228, 192, 94, 0.15), rgba(228, 192, 94, 0.08));">
                <span class="text-muted">نسبة الحضور</span>
                <h3 style="color:#8a6d1a;">{{ $totalReservations > 0 ? round(($totalCheckedIn/$totalReservations)*100,1) : 0 }}%</h3>
                <small class="text-muted">{{ $totalCheckedIn }} من {{ $totalReservations }}</small>
            </div>
        </div>
    </div>
</div>
@endif

</div>
