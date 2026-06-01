@extends('layouts.print')
@section('title', 'قائمة الضيوف - واتساب')

@section('content')
<div class="print-page">

    
    <div class="header">
        <div class="logo-circle">
            <img src="{{ asset('logo.png') }}" alt="logo" onerror="this.style.display='none'">
        </div>
        <div class="header-text">
            <h1>جامعة الموصل</h1>
            <h2>قاعة الدكتور محمود الجليلي</h2>
            <h3>قائمة ضيوف الوفود - أرقام الواتساب</h3>
        </div>
    </div>

    
    <div class="event-info">
        <div class="info-grid">
            <div><strong>الفعالية:</strong> {{ $event->title }}</div>
            <div><strong>التاريخ:</strong> {{ $event->start_datetime->format('Y-m-d') }}</div>
            <div><strong>الوقت:</strong> {{ $event->start_datetime->format('h:i A') }}</div>
            <div><strong>عدد الضيوف:</strong> {{ $bookings->count() }}</div>
        </div>
    </div>

    
    <div class="no-print" style="text-align: center; margin: 20px 0;">
        <button onclick="window.print()" class="btn btn-print">
            🖨️ طباعة الجدول
        </button>
        <a href="{{ route('dashboard.vip-guests', $event->uuid) }}" class="btn btn-back">
            ← رجوع
        </a>
    </div>

    
    <table class="guests-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>اسم الضيف</th>
                <th style="width: 150px;">رقم الجوال</th>
                <th style="width: 100px;">المقعد</th>
                <th style="width: 110px;">رقم QR</th>
                <th class="no-print" style="width: 140px;">واتساب</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $idx => $booking)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><strong>{{ $booking->guest_name }}</strong></td>
                <td dir="ltr">{{ $booking->guest_phone }}</td>
                <td>{{ $booking->seat?->section?->name }}-{{ $booking->seat?->row_number }}-{{ $booking->seat?->seat_number }}</td>
                <td><small>{{ $booking->qr_code }}</small></td>
                <td class="no-print">
                    @php
                        $phone = preg_replace('/[^0-9]/', '', $booking->guest_phone ?? '');
                        if (str_starts_with($phone, '0')) $phone = '964' . substr($phone, 1);
@endphp
                    <a href="https://wa.me/{{ $phone }}" target="_blank" class="wa-btn">
                        💬 فتح
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    
    <div class="footer">
        طُبع في: {{ now()->format('Y-m-d H:i') }} | إدارة مسرح جامعة الموصل
    </div>

</div>

<style>
    @page {
        size: A4;
        margin: 1.5cm;
    }

    body {
        font-family: 'Tajawal', 'Cairo', Arial, sans-serif;
        background: #fff;
        margin: 0;
        direction: rtl;
        color: #1f2937;
    }

    .print-page {
        max-width: 100%;
        padding: 20px;
    }

    .header {
        display: flex;
        gap: 16px;
        align-items: center;
        border-bottom: 3px solid #0C4A6E;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }

    .logo-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #0C4A6E;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .logo-circle img { width: 60px; height: 60px; }

    .header-text h1 { font-size: 20px; color: #0C4A6E; margin: 0 0 4px 0; font-weight: 800; }
    .header-text h2 { font-size: 16px; color: #475569; margin: 0 0 4px 0; font-weight: 600; }
    .header-text h3 { font-size: 15px; color: #C9A530; margin: 0; font-weight: 700; }

    .event-info {
        background: #F8FAFC;
        border-right: 4px solid #C9A530;
        padding: 14px 18px;
        margin-bottom: 20px;
        border-radius: 6px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        font-size: 14px;
    }
    .info-grid strong { color: #0C4A6E; margin-left: 8px; }

    .guests-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        background: #fff;
    }
    .guests-table thead {
        background: #0C4A6E;
        color: #fff;
    }
    .guests-table th {
        padding: 10px 8px;
        text-align: right;
        font-weight: 700;
        border: 1px solid #075985;
    }
    .guests-table td {
        padding: 8px;
        text-align: right;
        border: 1px solid #E2E8F0;
    }
    .guests-table tbody tr:nth-child(even) { background: #F8FAFC; }
    .guests-table tbody tr:hover { background: #FEF9E7; }

    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 12px;
        border-top: 1px solid #E2E8F0;
        font-size: 12px;
        color: #6b7280;
    }

    .btn-print {
        background: #0C4A6E;
        color: #fff;
        border: 0;
        padding: 10px 24px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        margin-left: 8px;
    }
    .btn-print:hover { background: #075985; }

    .btn-back {
        background: #6b7280;
        color: #fff;
        text-decoration: none;
        padding: 10px 24px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 14px;
        display: inline-block;
    }

    .wa-btn {
        background: #25D366;
        color: #fff;
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    @media print {
        .no-print { display: none !important; }
        body { font-size: 11pt; }
        .guests-table { page-break-inside: auto; }
        .guests-table tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
    }
</style>
@endsection
