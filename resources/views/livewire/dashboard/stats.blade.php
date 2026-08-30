<div>

    <div class="card-custom p-3 mb-3">
        <h5 class="mb-1" style="color: #0C4A6E;">
            <i class="bi bi-bar-chart-fill"></i>
            الإحصائيات
        </h5>
        <small class="text-muted">ملخص عام للنظام ونسب الحضور لكل فعالية</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md">
            <div class="stat-card" style="border-bottom: 4px solid #0C4A6E;">
                <div class="number" style="color: #0C4A6E;">{{ $totalEvents }}</div>
                <div class="label">
                    <i class="bi bi-calendar3" style="color: #0C4A6E;"></i>
                    إجمالي الفعاليات
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card" style="border-bottom: 4px solid #0C4A6E;">
                <div class="number" style="color: #0C4A6E;">{{ $publishedEvents }}</div>
                <div class="label">
                    <i class="bi bi-megaphone-fill" style="color: #0C4A6E;"></i>
                    المنشورة
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card" style="border-bottom: 4px solid #C9A530;">
                <div class="number" style="color: #A88729;">{{ $totalReservations }}</div>
                <div class="label">
                    <i class="bi bi-ticket-perforated-fill" style="color: #C9A530;"></i>
                    الحجوزات
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card" style="border-bottom: 4px solid #22C55E;">
                <div class="number" style="color: #22C55E;">{{ $totalCheckedIn }}</div>
                <div class="label">
                    <i class="bi bi-person-check-fill" style="color: #22C55E;"></i>
                    الحضور
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-card" style="border-bottom: 4px solid #22C55E;">
                <div class="number" style="color: #22C55E;">{{ $overallRate }}%</div>
                <div class="label">
                    <i class="bi bi-graph-up-arrow" style="color: #22C55E;"></i>
                    نسبة الحضور العامة
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom p-0" style="overflow: auto;">
        <table class="table table-hover align-middle mb-0" style="min-width: 720px;">
            <thead>
                <tr style="background: #0C4A6E;">
                    <th class="text-white py-3">الفعالية</th>
                    <th class="text-white text-center">التاريخ</th>
                    <th class="text-white text-center">الحالة</th>
                    <th class="text-white text-center">الحجوزات</th>
                    <th class="text-white text-center">الحضور</th>
                    <th class="text-white" style="min-width: 180px;">نسبة الحضور</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventStats as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['title'] }}</td>
                        <td class="text-center text-muted">{{ $row['date'] }}</td>
                        <td class="text-center">
                            @php
                                $statusLabel = [
                                    'draft'     => ['مسودة', 'secondary'],
                                    'pending'   => ['بانتظار الموافقة', 'warning'],
                                    'added'     => ['بانتظار الموافقة', 'warning'],
                                    'active'    => ['نشطة', 'info'],
                                    'published' => ['منشورة', 'primary'],
                                    'paused'    => ['موقوفة', 'warning'],
                                    'cancelled' => ['ملغاة', 'danger'],
                                    'ended'     => ['منتهية', 'dark'],
                                    'closed'    => ['مغلقة', 'dark'],
                                ][$row['status']] ?? [$row['status'], 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        </td>
                        <td class="text-center fw-bold">{{ $row['booked'] }}</td>
                        <td class="text-center fw-bold" style="color: #22C55E;">{{ $row['attended'] }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar"
                                         style="width: {{ $row['rate'] }}%; background: #0C4A6E;"></div>
                                </div>
                                <small class="fw-bold" style="min-width: 34px; color: #0C4A6E;">
                                    {{ $row['rate'] }}%
                                </small>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            لا توجد فعاليات بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
