<div>

{{-- معلومات الفعالية الملغاة --}}
<div class="card-custom p-4 mb-4" style="border-right: 5px solid #DC2626; background: linear-gradient(135deg, #fef2f2, #ffffff);">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="mb-2">
                <span class="badge bg-danger px-3 py-2" style="font-size: 14px;">
                    <i class="bi bi-x-octagon-fill"></i> فعالية ملغاة
                </span>
            </div>
            <h4 class="mb-1" style="color: #DC2626;">
                {{ $event->title }}
            </h4>
            @if($event->description)
            <p class="text-muted small mb-2">{{ $event->description }}</p>
            @endif
            <div class="small text-muted">
                <i class="bi bi-calendar-event"></i> كانت مقررة: {{ $event->start_datetime->format('Y-m-d H:i') }}
                @if($event->cancelled_at)
                <span class="ms-3">| <i class="bi bi-clock-history"></i> تاريخ الإلغاء: {{ $event->cancelled_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('dashboard.events') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع
        </a>
    </div>

    {{-- سبب الإلغاء --}}
    @if($event->cancellation_reason)
    <div class="alert alert-warning mt-3 mb-0">
        <h6 class="alert-heading mb-2">
            <i class="bi bi-info-circle-fill"></i> سبب الإلغاء (سيُرسل ضمن الرسالة):
        </h6>
        <p class="mb-0 fst-italic">{{ $event->cancellation_reason }}</p>
    </div>
    @else
    <div class="alert alert-secondary mt-3 mb-0">
        <small><i class="bi bi-info-circle"></i> لم يُحدّد سبب للإلغاء - سيتم إرسال رسالة اعتذار رسمية</small>
    </div>
    @endif
</div>

{{-- إحصائيات الإشعارات --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">إجمالي الوفود المتأثرين</div>
                    <h3 class="mb-0" style="color: #0C4A6E;">{{ $vipBookings->count() }}</h3>
                </div>
                <div style="background: rgba(12, 74, 110, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-people-fill" style="color: #0C4A6E; font-size: 28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">حالة الإشعارات</div>
                    <h3 class="mb-0" style="color: #25D366;">جاهزة للإرسال</h3>
                </div>
                <div style="background: rgba(37, 211, 102, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-whatsapp" style="color: #25D366; font-size: 28px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- زر الإرسال الجماعي --}}
@if($vipBookings->count() > 0)
<div class="card-custom p-4 mb-4" style="background: linear-gradient(135deg, #d1fae5, #ffffff);">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1" style="color: #065f46;">
                <i class="bi bi-send-fill"></i> إرسال جماعي للوفود
            </h5>
            <p class="mb-0 small text-muted">
                ستُفتح <strong>{{ $vipBookings->count() }}</strong> نافذة واتساب — اضغط Enter في كل نافذة لإرسال الرسالة
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span id="cancel-progress" class="text-muted small" style="display:none;">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                جارٍ الفتح: <span id="cancel-progress-current">0</span>/<span id="cancel-progress-total">0</span>
            </span>
            <button id="sendAllBtn"
                    type="button"
                    class="btn btn-lg"
                    style="background: #25D366; color: #fff; font-weight: 700;"
                    onclick="sendAllCancellationNotifications()">
                <i class="bi bi-whatsapp"></i> إرسال للكل ({{ $vipBookings->count() }})
            </button>
        </div>
    </div>
    <div class="alert alert-info mt-3 mb-0 small">
        <i class="bi bi-info-circle"></i>
        <strong>نصيحة:</strong> قبل الضغط، تأكد أن
        <a href="https://web.whatsapp.com" target="_blank" style="color: #075985; text-decoration: underline;">واتساب ويب</a>
        مسجّل دخوله في متصفحك لتسريع العملية.
    </div>
</div>
@endif

{{-- جدول الوفود --}}
@if($vipBookings->count() > 0)
<div class="card-custom p-4">
    <h6 class="mb-3"><i class="bi bi-list-ul"></i> قائمة الوفود المتأثرين</h6>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الضيف</th>
                    <th>رقم الجوال</th>
                    <th>المقعد</th>
                    <th>القسم</th>
                    <th style="width: 180px; text-align: center;">إرسال فردي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vipBookings as $booking)
                @php $cancelLink = $this->getCancellationWhatsAppLink($booking->id); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $booking->guest_name }}</strong></td>
                    <td dir="ltr">{{ $booking->guest_phone }}</td>
                    <td>
                        <span class="badge" style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; padding: 6px 12px;">
                            {{ $booking->seat->label }}
                        </span>
                    </td>
                    <td>القسم {{ $booking->seat->section->name }}</td>
                    <td style="text-align: center;">
                        <a href="{{ $cancelLink }}"
                           target="_blank"
                           rel="noopener"
                           class="btn btn-sm wa-cancel-link"
                           style="background: #25D366; color: #fff; font-weight: 600;"
                           data-link="{{ $cancelLink }}">
                            <i class="bi bi-whatsapp"></i> إرسال إشعار
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card-custom p-5 text-center">
    <i class="bi bi-check-circle" style="font-size: 60px; color: #16a34a;"></i>
    <h5 class="mt-3" style="color: #16a34a;">لا يوجد وفود لإشعارهم</h5>
    <p class="text-muted">هذه الفعالية لم يكن بها أي حجوزات وفود نشطة عند الإلغاء</p>
    <a href="{{ route('dashboard.events') }}" class="btn btn-primary mt-2">
        <i class="bi bi-arrow-right"></i> العودة للفعاليات
    </a>
</div>
@endif

{{-- معاينة الرسالة الرسمية --}}
@if($vipBookings->count() > 0)
<div class="card-custom p-4 mt-4">
    <h6 class="mb-3"><i class="bi bi-eye"></i> معاينة الرسالة المُرسلة</h6>
    <div class="p-3 rounded" style="background: #ECE5DD; border-radius: 8px;">
        <div style="background: #DCF8C6; padding: 16px 20px; border-radius: 8px; max-width: 90%; box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);">
<pre style="margin: 0; font-family: 'Cairo', 'Tajawal', sans-serif; white-space: pre-wrap; font-size: 14px; line-height: 1.8; color: #303030;"><strong>جامعة الموصل - مسرح الجامعة</strong>
─────────────────────────

السلام عليكم ورحمة الله وبركاته

الأستاذ/ة الفاضل/ة: <strong>[اسم الضيف]</strong>

تحية طيبة وبعد،

نأسف لإبلاغكم بإلغاء الفعالية الموسومة بـ:
<strong>{{ $event->title }}</strong>

والتي كان من المقرر إقامتها بتاريخ {{ $event->start_datetime->format('Y-m-d') }} في تمام الساعة {{ $event->start_datetime->format('H:i') }}.

@if($event->cancellation_reason)<strong>سبب الإلغاء:</strong>
{{ $event->cancellation_reason }}

@endif
نعتذر عن أي إزعاج قد يسببه ذلك، ونشكر لكم تفهمكم وحسن تعاونكم.

تفضلوا بقبول فائق الاحترام والتقدير،،،

<strong>إدارة مسرح جامعة الموصل</strong></pre>
        </div>
    </div>
    <small class="text-muted mt-2 d-block">
        <i class="bi bi-info-circle"></i> اسم الضيف يُستبدل تلقائياً لكل وفد عند الإرسال
    </small>
</div>
@endif

{{-- JavaScript للإرسال الجماعي --}}
<script>
function sendAllCancellationNotifications() {
    const links = document.querySelectorAll('.wa-cancel-link');
    const total = links.length;

    if (total === 0) {
        alert('لا يوجد وفود لإرسال إشعارات لهم');
        return;
    }

    if (!confirm('سيتم فتح ' + total + ' نافذة واتساب بالتتابع.\nبعد فتحها، اضغط Enter في كل نافذة لإرسال الرسالة.\n\nهل تريد المتابعة؟')) {
        return;
    }

    const progressEl = document.getElementById('cancel-progress');
    const currentEl = document.getElementById('cancel-progress-current');
    const totalEl = document.getElementById('cancel-progress-total');
    const btn = document.getElementById('sendAllBtn');

    totalEl.textContent = total;
    currentEl.textContent = 0;
    progressEl.style.display = 'inline-block';
    btn.disabled = true;

    links.forEach((link, index) => {
        setTimeout(() => {
            window.open(link.dataset.link, '_blank');
            currentEl.textContent = index + 1;

            if (index === total - 1) {
                setTimeout(() => {
                    progressEl.style.display = 'none';
                    btn.disabled = false;
                    alert('تم فتح ' + total + ' نافذة واتساب بنجاح\nاضغط Enter في كل نافذة لإرسال الرسالة');
                }, 500);
            }
        }, index * 800);
    });
}
</script>

</div>
