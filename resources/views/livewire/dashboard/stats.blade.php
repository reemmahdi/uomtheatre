<div>
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
        <div class="stat-card" style="border-bottom:3px solid #075985;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#075985;">{{ $publishedEvents }}</div>
                    <div class="label">منشورة</div>
                </div>
                <div class="icon" style="background:rgba(7, 89, 133, 0.1);color:#075985;">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #0369A1;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#0369A1;">{{ $totalReservations }}</div>
                    <div class="label">إجمالي الحجوزات</div>
                </div>
                <div class="icon" style="background:rgba(3, 105, 161, 0.1);color:#0369A1;">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-bottom:3px solid #E4C05E;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="number" style="color:#8a6d1a;">{{ $totalCheckedIn }}</div>
                    <div class="label">إجمالي الحضور</div>
                </div>
                <div class="icon" style="background:rgba(228, 192, 94, 0.2);color:#8a6d1a;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <h6 class="mb-3"><i class="bi bi-bar-chart-line"></i> ملخص النظام</h6>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, rgba(12, 74, 110, 0.08), rgba(7, 89, 133, 0.06));">
                <span class="text-muted">إجمالي المقاعد</span>
                <h3 style="color:#0C4A6E;">{{ config('theatre.total_seats') }}</h3>
                <small class="text-muted">منها {{ config('theatre.vip_seats') }} مقعد وفود</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, rgba(228, 192, 94, 0.15), rgba(228, 192, 94, 0.08));">
                <span class="text-muted">نسبة الحضور</span>
                <h3 style="color:#8a6d1a;">{{ $totalReservations > 0 ? round(($totalCheckedIn/$totalReservations)*100,1) : 0 }}%</h3>
                <small class="text-muted">{{ $totalCheckedIn }} حضروا من {{ $totalReservations }} حجز</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded" style="background: linear-gradient(135deg, rgba(3, 105, 161, 0.08), rgba(2, 132, 199, 0.06));">
                <span class="text-muted">أقسام المسرح</span>
                <h3 style="color:#0369A1;">{{ config('theatre.sections') }}</h3>
                <small class="text-muted">A, B, C (عادي) + D, E, F (VIP)</small>
            </div>
        </div>
    </div>
</div>
</div>
