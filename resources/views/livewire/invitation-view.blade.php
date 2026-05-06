<div>

@if($notFound)
    {{-- ════════════════════════════════════════
         حالة: الدعوة غير موجودة أو ملغاة
         ════════════════════════════════════════ --}}
    <div class="invitation-error-page">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-x-octagon-fill"></i>
            </div>
            <h2>الدعوة غير صالحة</h2>
            <p>لم نتمكن من العثور على هذه الدعوة، أو أنها ملغاة.</p>
            <p class="text-muted small">إذا كنتم تعتقدون أن هذا خطأ، يرجى التواصل مع إدارة المسرح.</p>
        </div>
    </div>
@else
    {{-- ════════════════════════════════════════
         صفحة الدعوة الرئيسية (تصميم جديد - مبسّط وأنيق)
         ════════════════════════════════════════ --}}
    <div class="invitation-page">

        {{-- تنبيه إذا كانت الفعالية ملغاة --}}
        @if($reservation->event->status->name === 'cancelled')
        <div class="cancelled-banner">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>تنبيه: هذه الفعالية ملغاة</strong>
                @if($reservation->event->cancellation_reason)
                <br><small>{{ $reservation->event->cancellation_reason }}</small>
                @endif
            </div>
        </div>
        @endif

        <div class="invitation-card">

            {{-- ✨ الترويسة المبسطة --}}
            <div class="card-header">
                <div class="logo-circle">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="جامعة الموصل"
                         onerror="this.outerHTML='<i class=\'bi bi-mortarboard-fill\'></i>'">
                </div>
                <h1 class="university-name">جامعة الموصل</h1>
                <p class="theatre-name">قاعة الدكتور محمود الجليلي</p>
            </div>

            {{-- ✨ شارة "دعوة" --}}
            <div class="invitation-badge">
                <i class="bi bi-envelope-paper-heart-fill"></i>
                دعوة كريمة
            </div>

            {{-- ✨ اسم الضيف --}}
            <div class="guest-section">
                <p class="greeting">السلام عليكم ورحمة الله وبركاته</p>
                <p class="honorific">الأستاذ/ة الفاضل/ة</p>
                <h2 class="guest-name">{{ $reservation->guest_name }}</h2>
                <p class="invitation-text">يسعدنا دعوتكم لحضور</p>
            </div>

            {{-- ✨ اسم الفعالية --}}
            <div class="event-title-section">
                <h3 class="event-title">{{ $reservation->event->title }}</h3>
            </div>

            {{-- ✨ تفاصيل الموعد والمكان --}}
            <div class="info-cards">
                {{-- التاريخ --}}
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="info-content">
                        <div class="info-label">التاريخ</div>
                        <div class="info-value" dir="ltr">{{ $reservation->event->start_datetime->format('Y-m-d') }}</div>
                    </div>
                </div>

                {{-- الوقت --}}
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-clock"></i></div>
                    <div class="info-content">
                        <div class="info-label">الوقت</div>
                        <div class="info-value" dir="ltr">
                            @php
                                $hour = (int) $reservation->event->start_datetime->format('G');
                                $period = $hour < 12 ? 'صباحاً' : 'مساءً';
                                $time12 = $reservation->event->start_datetime->format('h:i');
                            @endphp
                            {{ $time12 }} <span class="period">{{ $period }}</span>
                        </div>
                    </div>
                </div>

                {{-- المكان --}}
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="info-content">
                        <div class="info-label">المكان</div>
                        <div class="info-value">مسرح جامعة الموصل</div>
                    </div>
                </div>
            </div>

            {{-- ✨ معلومات المقعد - بصورة أوضح --}}
            <div class="seat-section">
                <div class="seat-label-top">
                    <i class="bi bi-bookmark-star-fill"></i> مقعدكم المخصص
                </div>
                <div class="seat-main-display">
                    {{ $reservation->seat->label }}
                </div>
                <div class="seat-details">
                    <span class="seat-detail">
                        <strong>القسم:</strong> {{ $reservation->seat->section->name }}
                    </span>
                    <span class="seat-divider"></span>
                    <span class="seat-detail">
                        <strong>الصف:</strong> {{ $reservation->seat->row_number }}
                    </span>
                    <span class="seat-divider"></span>
                    <span class="seat-detail">
                        <strong>الرقم:</strong> {{ $reservation->seat->seat_number }}
                    </span>
                </div>
            </div>

            {{-- ✨ رمز QR - أوضح وأكبر --}}
            <div class="qr-section">
                <div class="qr-label">
                    <i class="bi bi-qr-code-scan"></i> رمز الدخول
                </div>
                <div class="qr-frame">
                    <img src="data:image/svg+xml;base64,{{ $qrImage }}" alt="QR Code" class="qr-image">
                </div>
                <p class="qr-help">
                    <i class="bi bi-info-circle"></i>
                    يُرجى إظهار هذا الرمز عند الدخول
                </p>
            </div>

            {{-- ✨ التذييل المختصر --}}
            <div class="card-footer">
                <p class="footer-greeting">نتشرف بحضوركم الكريم</p>
                <p class="footer-signature">إدارة مسرح جامعة الموصل</p>
            </div>

        </div>

        {{-- ✨ زر طباعة (للضيف لو حاب يطبع الدعوة) --}}
        <div class="action-buttons">
            <button onclick="window.print()" class="action-btn">
                <i class="bi bi-printer"></i> طباعة الدعوة
            </button>
        </div>

    </div>
@endif

{{-- ════════════════════════════════════════
     التنسيقات - مبسطة، عصرية، Mobile-First
     ════════════════════════════════════════ --}}
<style>
    /* خلفية الصفحة */
    body {
        background: linear-gradient(135deg, #0C4A6E 0%, #075985 50%, #0369A1 100%);
        min-height: 100vh;
        font-family: 'Cairo', 'Tajawal', sans-serif;
        padding: 16px 0;
        margin: 0;
    }

    .invitation-page {
        max-width: 480px;
        margin: 0 auto;
        padding: 0 12px;
    }

    /* ════════════ بانر الإلغاء ════════════ */
    .cancelled-banner {
        background: #DC2626;
        color: white;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
    }

    .cancelled-banner i {
        font-size: 22px;
        flex-shrink: 0;
    }

    /* ════════════ البطاقة الرئيسية ════════════ */
    .invitation-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
        overflow: hidden;
        position: relative;
    }

    /* خط ذهبي علوي مميز */
    .invitation-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #0C4A6E, #075985, #0369A1);
    }

    /* ════════════ الترويسة ════════════ */
    .card-header {
        text-align: center;
        padding: 28px 20px 16px;
    }

    .logo-circle {
        width: 72px;
        height: 72px;
        margin: 0 auto 12px;
        border-radius: 50%;
        background: #f0f9ff;
        border: 3px solid #0C4A6E;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(12, 74, 110, 0.15);
    }

    .logo-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .logo-circle i {
        font-size: 36px;
        color: #0C4A6E;
    }

    .university-name {
        color: #0C4A6E;
        font-size: 22px;
        font-weight: 800;
        margin: 0 0 4px;
        letter-spacing: 0.3px;
    }

    .theatre-name {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* ════════════ شارة الدعوة ════════════ */
    .invitation-badge {
        margin: 0 auto 20px;
        max-width: fit-content;
        background: linear-gradient(135deg, #0C4A6E, #075985);
        color: #fff;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(12, 74, 110, 0.3);
    }

    /* ════════════ قسم الضيف ════════════ */
    .guest-section {
        text-align: center;
        padding: 0 24px 20px;
    }

    .greeting {
        color: #475569;
        font-size: 14px;
        margin: 0 0 12px;
        font-weight: 500;
    }

    .honorific {
        color: #64748b;
        font-size: 13px;
        margin: 0 0 4px;
    }

    .guest-name {
        color: #0C4A6E;
        font-size: 24px;
        font-weight: 800;
        margin: 0 0 12px;
        line-height: 1.3;
    }

    .invitation-text {
        color: #475569;
        font-size: 15px;
        margin: 0;
        font-weight: 500;
    }

    /* ════════════ عنوان الفعالية ════════════ */
    .event-title-section {
        margin: 0 20px 24px;
        padding: 18px 16px;
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border-radius: 12px;
        border-right: 4px solid #0C4A6E;
        text-align: center;
    }

    .event-title {
        color: #0C4A6E;
        font-size: 19px;
        font-weight: 800;
        margin: 0;
        line-height: 1.4;
    }

    /* ════════════ كروت المعلومات (تاريخ/وقت/مكان) ════════════ */
    .info-cards {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 0 20px 24px;
    }

    .info-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .info-card:hover {
        border-color: #0C4A6E;
        box-shadow: 0 2px 8px rgba(12, 74, 110, 0.1);
    }

    .info-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0C4A6E, #075985);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 15px;
        color: #0C4A6E;
        font-weight: 700;
    }

    .info-value .period {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        margin-right: 4px;
    }

    /* ════════════ قسم المقعد ════════════ */
    .seat-section {
        margin: 0 20px 24px;
        padding: 20px 16px;
        background: linear-gradient(135deg, #0C4A6E, #075985);
        border-radius: 14px;
        text-align: center;
        color: #fff;
        box-shadow: 0 6px 20px rgba(12, 74, 110, 0.3);
    }

    .seat-label-top {
        font-size: 13px;
        font-weight: 600;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .seat-main-display {
        font-size: 36px;
        font-weight: 800;
        letter-spacing: 2px;
        margin: 8px 0;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        font-family: 'Tajawal', sans-serif;
    }

    .seat-details {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 13px;
        flex-wrap: wrap;
        opacity: 0.95;
    }

    .seat-detail {
        white-space: nowrap;
    }

    .seat-divider {
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
    }

    /* ════════════ قسم QR ════════════ */
    .qr-section {
        text-align: center;
        padding: 0 20px 24px;
    }

    .qr-label {
        color: #0C4A6E;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .qr-frame {
        display: inline-block;
        padding: 14px;
        background: #fff;
        border: 2px solid #0C4A6E;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(12, 74, 110, 0.2);
    }

    .qr-image {
        display: block;
        width: 200px;
        height: 200px;
    }

    .qr-help {
        margin: 12px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .qr-help i {
        color: #0C4A6E;
    }

    /* ════════════ التذييل ════════════ */
    .card-footer {
        text-align: center;
        padding: 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    .footer-greeting {
        color: #0C4A6E;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 4px;
    }

    .footer-signature {
        color: #64748b;
        font-size: 13px;
        margin: 0;
        font-weight: 500;
    }

    /* ════════════ أزرار الإجراءات (طباعة) ════════════ */
    .action-buttons {
        margin-top: 16px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.95);
        color: #0C4A6E;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .action-btn:hover {
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    /* ════════════ صفحة الخطأ ════════════ */
    .invitation-error-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .error-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px 30px;
        text-align: center;
        max-width: 380px;
        width: 100%;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }

    .error-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 16px;
        background: #fee2e2;
        color: #DC2626;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }

    .error-card h2 {
        color: #DC2626;
        font-size: 22px;
        margin: 0 0 12px;
        font-weight: 800;
    }

    .error-card p {
        color: #475569;
        font-size: 14px;
        margin: 0 0 8px;
        line-height: 1.5;
    }

    /* ════════════ Print Styles (للطباعة) ════════════ */
    @media print {
        body {
            background: #fff;
            padding: 0;
        }

        .invitation-card {
            box-shadow: none;
            border: 2px solid #0C4A6E;
        }

        .action-buttons,
        .cancelled-banner {
            display: none !important;
        }
    }

    /* ════════════ Responsive للجوال ════════════ */
    @media (max-width: 380px) {
        .university-name {
            font-size: 19px;
        }

        .guest-name {
            font-size: 21px;
        }

        .event-title {
            font-size: 17px;
        }

        .seat-main-display {
            font-size: 32px;
        }

        .qr-image {
            width: 180px;
            height: 180px;
        }

        .info-card {
            padding: 10px 12px;
        }

        .seat-details {
            font-size: 12px;
            gap: 6px;
        }
    }
</style>

</div>
