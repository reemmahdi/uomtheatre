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
         صفحة الدعوة الرئيسية
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

            {{-- الترويسة --}}
            <div class="invitation-header">
                <div class="header-decoration-top"></div>
                <div class="university-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="جامعة الموصل" onerror="this.style.display='none'">
                </div>
                <h1 class="university-name">جامعة الموصل</h1>
                <p class="theatre-name">مسرح الجامعة</p>
                <div class="header-decoration-bottom"></div>
            </div>

            {{-- العنوان الرئيسي --}}
            <div class="invitation-title-section">
                <p class="invitation-label">دعوة كريمة</p>
                <div class="title-divider"></div>
            </div>

            {{-- اسم الضيف --}}
            <div class="guest-section">
                <p class="greeting">السلام عليكم ورحمة الله وبركاته</p>
                <p class="guest-honorific">الأستاذ/ة الفاضل/ة</p>
                <h2 class="guest-name">{{ $reservation->guest_name }}</h2>
                <p class="invitation-text">يسعدنا دعوتكم لحضور</p>
            </div>

            {{-- اسم الفعالية --}}
            <div class="event-section">
                <h3 class="event-title">{{ $reservation->event->title }}</h3>
                <div class="event-details">
                    <div class="detail-item">
                        <i class="bi bi-calendar-event"></i>
                        <span>{{ $reservation->event->start_datetime->format('Y/m/d') }}</span>
                    </div>
                    <div class="detail-divider"></div>
                    <div class="detail-item">
                        <i class="bi bi-clock"></i>
                        <span>{{ $reservation->event->start_datetime->format('H:i') }}</span>
                    </div>
                    <div class="detail-divider"></div>
                    <div class="detail-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>مسرح جامعة الموصل</span>
                    </div>
                </div>
            </div>

            {{-- معلومات المقعد --}}
            <div class="seat-section">
                <h4 class="section-heading">
                    <i class="bi bi-bookmark-star-fill"></i> مقعدكم
                </h4>
                <div class="seat-info-grid">
                    <div class="seat-info-box">
                        <div class="info-label">القسم</div>
                        <div class="info-value">{{ $reservation->seat->section->name }}</div>
                    </div>
                    <div class="seat-info-box">
                        <div class="info-label">الصف</div>
                        <div class="info-value">{{ $reservation->seat->row_number }}</div>
                    </div>
                    <div class="seat-info-box">
                        <div class="info-label">رقم المقعد</div>
                        <div class="info-value">{{ $reservation->seat->seat_number }}</div>
                    </div>
                    <div class="seat-info-box highlighted">
                        <div class="info-label">الرمز</div>
                        <div class="info-value">{{ $reservation->seat->label }}</div>
                    </div>
                </div>
            </div>

            {{-- الجالسون بجانبكم --}}
            @if(count($neighbors) > 0)
            <div class="neighbors-section">
                <h4 class="section-heading">
                    <i class="bi bi-people-fill"></i> الجالسون بجانبكم
                </h4>
                <div class="neighbors-grid">
                    @foreach($neighbors as $neighbor)
                    <div class="neighbor-card">
                        <div class="neighbor-direction">
                            <i class="bi {{ $neighbor['icon'] }}"></i>
                            {{ $neighbor['label'] }}
                        </div>
                        <div class="neighbor-name">{{ $neighbor['name'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- رمز QR --}}
            <div class="qr-section">
                <h4 class="section-heading">
                    <i class="bi bi-qr-code-scan"></i> رمز الدخول
                </h4>
                <div class="qr-code-wrapper">
                    <div class="qr-code-frame">
                        <img src="data:image/svg+xml;base64,{{ $qrImage }}" alt="QR Code" class="qr-image">
                    </div>
                    <p class="qr-instructions">
                        <i class="bi bi-info-circle-fill"></i>
                        يرجى إظهار هذا الرمز عند الدخول للمسرح
                    </p>
                    <p class="qr-text">{{ $reservation->qr_code }}</p>
                </div>
            </div>

            {{-- التذييل --}}
            <div class="invitation-footer">
                <p class="footer-text">نتشرف بحضوركم الكريم</p>
                <p class="footer-signature">
                    تفضلوا بقبول فائق الاحترام والتقدير
                    <br>
                    <strong>إدارة مسرح جامعة الموصل</strong>
                </p>
                <div class="footer-decoration"></div>
            </div>

        </div>
    </div>
@endif

{{-- ════════════════════════════════════════
     التنسيقات الكاملة
     ════════════════════════════════════════ --}}
<style>
    /* خلفية الصفحة */
    body {
        background: linear-gradient(135deg, #0C4A6E 0%, #075985 50%, #0369A1 100%);
        min-height: 100vh;
        font-family: 'Cairo', 'Tajawal', sans-serif;
        padding: 20px 0;
        margin: 0;
    }

    .invitation-page {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* بانر الإلغاء */
    .cancelled-banner {
        background: linear-gradient(135deg, #DC2626, #B91C1C);
        color: white;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
    }

    .cancelled-banner i {
        font-size: 28px;
        flex-shrink: 0;
    }

    /* البطاقة الرئيسية */
    .invitation-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    /* ═══════════════════════════════════════════
       الترويسة
       ═══════════════════════════════════════════ */
    .invitation-header {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        color: white;
        padding: 30px 25px 35px;
        text-align: center;
        position: relative;
    }

    .header-decoration-top,
    .header-decoration-bottom {
        position: absolute;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, #E4C05E 20%, #E4C05E 80%, transparent);
    }

    .header-decoration-top { top: 12px; }
    .header-decoration-bottom { bottom: 12px; }

    .university-logo {
        width: 70px;
        height: 70px;
        margin: 0 auto 12px;
        background: white;
        border-radius: 50%;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .university-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .university-name {
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 4px;
        letter-spacing: 0.5px;
    }

    .theatre-name {
        font-size: 16px;
        opacity: 0.9;
        margin: 0;
        font-weight: 500;
        letter-spacing: 1.5px;
    }

    /* ═══════════════════════════════════════════
       عنوان الدعوة
       ═══════════════════════════════════════════ */
    .invitation-title-section {
        text-align: center;
        padding: 30px 25px 15px;
    }

    .invitation-label {
        font-size: 22px;
        font-weight: 700;
        color: #0C4A6E;
        margin: 0;
        letter-spacing: 2px;
    }

    .title-divider {
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #E4C05E, #C9A445);
        margin: 12px auto 0;
        border-radius: 2px;
    }

    /* ═══════════════════════════════════════════
       اسم الضيف
       ═══════════════════════════════════════════ */
    .guest-section {
        text-align: center;
        padding: 20px 25px 25px;
    }

    .greeting {
        color: #6b7280;
        font-size: 14px;
        margin: 0 0 15px;
    }

    .guest-honorific {
        color: #6b7280;
        font-size: 15px;
        margin: 0 0 6px;
    }

    .guest-name {
        font-size: 26px;
        font-weight: 800;
        color: #0C4A6E;
        margin: 0 0 15px;
    }

    .invitation-text {
        color: #4b5563;
        font-size: 15px;
        margin: 0;
    }

    /* ═══════════════════════════════════════════
       اسم الفعالية
       ═══════════════════════════════════════════ */
    .event-section {
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        margin: 0 25px;
        padding: 25px 20px;
        border-radius: 14px;
        text-align: center;
        border: 2px solid #E4C05E;
    }

    .event-title {
        font-size: 22px;
        font-weight: 800;
        color: #78350F;
        margin: 0 0 18px;
        line-height: 1.4;
    }

    .event-details {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #92400E;
        font-weight: 600;
        font-size: 14px;
    }

    .detail-item i {
        color: #C9A445;
        font-size: 16px;
    }

    .detail-divider {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #C9A445;
    }

    /* ═══════════════════════════════════════════
       معلومات المقعد
       ═══════════════════════════════════════════ */
    .seat-section,
    .neighbors-section,
    .qr-section {
        padding: 25px 25px 5px;
    }

    .section-heading {
        font-size: 16px;
        font-weight: 700;
        color: #0C4A6E;
        margin: 0 0 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-heading i {
        color: #C9A445;
        font-size: 18px;
    }

    .seat-info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .seat-info-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 12px 8px;
        text-align: center;
    }

    .seat-info-box.highlighted {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        border-color: #0C4A6E;
        color: white;
    }

    .info-label {
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .seat-info-box.highlighted .info-label {
        color: #cbd5e1;
    }

    .info-value {
        font-size: 18px;
        font-weight: 800;
        color: #0C4A6E;
    }

    .seat-info-box.highlighted .info-value {
        color: #E4C05E;
    }

    /* ═══════════════════════════════════════════
       الجالسون بجانبكم
       ═══════════════════════════════════════════ */
    .neighbors-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .neighbor-card {
        background: #F0F9FF;
        border: 1px solid #BAE6FD;
        border-radius: 10px;
        padding: 12px;
    }

    .neighbor-direction {
        font-size: 12px;
        color: #0369A1;
        font-weight: 600;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .neighbor-direction i {
        font-size: 14px;
    }

    .neighbor-name {
        font-size: 15px;
        color: #0C4A6E;
        font-weight: 700;
    }

    /* ═══════════════════════════════════════════
       رمز QR
       ═══════════════════════════════════════════ */
    .qr-code-wrapper {
        text-align: center;
    }

    .qr-code-frame {
        display: inline-block;
        background: white;
        padding: 15px;
        border-radius: 16px;
        border: 3px solid #E4C05E;
        box-shadow: 0 8px 20px rgba(228, 192, 94, 0.3);
        position: relative;
    }

    .qr-code-frame::before,
    .qr-code-frame::after {
        content: '';
        position: absolute;
        width: 25px;
        height: 25px;
        border: 3px solid #C9A445;
    }

    .qr-code-frame::before {
        top: -5px;
        right: -5px;
        border-left: none;
        border-bottom: none;
        border-radius: 0 8px 0 0;
    }

    .qr-code-frame::after {
        bottom: -5px;
        left: -5px;
        border-right: none;
        border-top: none;
        border-radius: 0 0 0 8px;
    }

    .qr-image {
        width: 240px;
        height: 240px;
        display: block;
    }

    .qr-instructions {
        margin: 15px 0 8px;
        color: #4b5563;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .qr-instructions i {
        color: #0369A1;
    }

    .qr-text {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        color: #6b7280;
        background: #F3F4F6;
        padding: 6px 14px;
        border-radius: 6px;
        display: inline-block;
        margin: 5px 0 0;
        letter-spacing: 1px;
    }

    /* ═══════════════════════════════════════════
       التذييل
       ═══════════════════════════════════════════ */
    .invitation-footer {
        text-align: center;
        padding: 30px 25px 25px;
        margin-top: 15px;
        background: linear-gradient(180deg, transparent, #F8FAFC);
    }

    .footer-text {
        font-size: 16px;
        color: #0C4A6E;
        font-weight: 700;
        margin: 0 0 12px;
    }

    .footer-signature {
        color: #6b7280;
        font-size: 13px;
        line-height: 1.8;
        margin: 0;
    }

    .footer-signature strong {
        color: #0C4A6E;
        font-size: 14px;
    }

    .footer-decoration {
        width: 100px;
        height: 3px;
        background: linear-gradient(90deg, transparent, #E4C05E, transparent);
        margin: 18px auto 0;
        border-radius: 2px;
    }

    /* ═══════════════════════════════════════════
       صفحة الخطأ
       ═══════════════════════════════════════════ */
    .invitation-error-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .error-card {
        background: white;
        padding: 40px 30px;
        border-radius: 16px;
        text-align: center;
        max-width: 400px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    }

    .error-icon {
        font-size: 64px;
        color: #DC2626;
        margin-bottom: 15px;
    }

    .error-card h2 {
        color: #0C4A6E;
        margin-bottom: 10px;
    }

    .error-card p {
        color: #6b7280;
        margin: 0 0 8px;
    }

    /* ═══════════════════════════════════════════
       Responsive للموبايل
       ═══════════════════════════════════════════ */
    @media (max-width: 480px) {
        .invitation-page {
            padding: 0 10px;
        }

        .invitation-card {
            border-radius: 14px;
        }

        .university-name {
            font-size: 24px;
        }

        .guest-name {
            font-size: 22px;
        }

        .event-title {
            font-size: 18px;
        }

        .seat-info-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .neighbors-grid {
            grid-template-columns: 1fr;
        }

        .qr-image {
            width: 200px;
            height: 200px;
        }
    }

    /* ═══════════════════════════════════════════
       طباعة
       ═══════════════════════════════════════════ */
    @media print {
        body {
            background: white;
        }

        .invitation-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>

</div>
