@extends('layouts.print')
@section('title', 'ملصقات مقاعد الضيوف')

@section('content')
<div class="print-page">

    
    <div class="no-print" style="text-align: center; margin: 20px 0; padding: 20px; background: #F8FAFC; border-radius: 8px;">
        <h4 style="color: #0C4A6E; margin-bottom: 8px;">
            🏷️ ملصقات مقاعد الضيوف — {{ $event->title }}
        </h4>
        <p style="color: #475569; margin-bottom: 12px;">
            عدد الملصقات: <strong>{{ $bookings->count() }}</strong> | 
            تُطبع بحجم A4 (3×4 ملصقات في كل صفحة)
        </p>
        <button onclick="window.print()" class="btn btn-print">
            🖨️ طباعة الملصقات
        </button>
        <a href="{{ route('dashboard.vip-guests', $event->uuid) }}" class="btn btn-back">
            ← رجوع
        </a>
    </div>

    
    <div class="stickers-grid">
        @foreach($bookings as $booking)
        <div class="sticker">
            
            <div class="sticker-header">
                <div class="event-name">{{ Str::limit($event->title, 30) }}</div>
                <div class="event-date">{{ $event->start_datetime->format('Y-m-d') }}</div>
            </div>

            
            <div class="sticker-body">
                <div class="guest-label">السيد/ة الفاضل/ة</div>
                <div class="guest-name">{{ $booking->guest_name }}</div>

                <div class="seat-info">
                    <div class="seat-row">
                        <span class="seat-label">القسم:</span>
                        <strong>{{ $booking->seat?->section?->name }}</strong>
                    </div>
                    <div class="seat-row">
                        <span class="seat-label">الصف:</span>
                        <strong>{{ $booking->seat?->row_number }}</strong>
                    </div>
                    <div class="seat-row">
                        <span class="seat-label">المقعد:</span>
                        <strong>{{ $booking->seat?->seat_number }}</strong>
                    </div>
                </div>

                
                <div class="seat-code">
                    {{ $booking->seat?->section?->name }}-{{ str_pad($booking->seat?->row_number ?? 0, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($booking->seat?->seat_number ?? 0, 2, '0', STR_PAD_LEFT) }}
                </div>
            </div>

            
            <div class="sticker-footer">
                مسرح جامعة الموصل
            </div>
        </div>
        @endforeach
    </div>

</div>

<style>
    @page {
        size: A4;
        margin: 0.8cm;
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
    }

    /* ════ شبكة الملصقات: 3 أعمدة × 4 صفوف = 12 لكل صفحة ════ */
    .stickers-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 4px;
    }

    .sticker {
        background: #fff;
        border: 2px solid #0C4A6E;
        border-radius: 10px;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .sticker-header {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        color: #fff;
        padding: 8px 10px;
        text-align: center;
    }
    .event-name {
        font-weight: 700;
        font-size: 12px;
        line-height: 1.3;
        margin-bottom: 2px;
    }
    .event-date {
        font-size: 10px;
        opacity: 0.9;
    }

    .sticker-body {
        padding: 12px 10px;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .guest-label {
        font-size: 10px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .guest-name {
        font-size: 16px;
        font-weight: 800;
        color: #0C4A6E;
        margin-bottom: 12px;
        line-height: 1.3;
        word-wrap: break-word;
    }

    .seat-info {
        background: #FEF9E7;
        border: 1px solid #C9A530;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 8px;
        width: 100%;
        max-width: 180px;
    }
    .seat-row {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin: 2px 0;
    }
    .seat-label { color: #6b7280; }
    .seat-row strong { color: #0C4A6E; font-size: 13px; }

    .seat-code {
        background: #0C4A6E;
        color: #fff;
        padding: 4px 12px;
        border-radius: 12px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .sticker-footer {
        background: #C9A530;
        color: #fff;
        text-align: center;
        padding: 4px;
        font-size: 10px;
        font-weight: 700;
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

    @media print {
        .no-print { display: none !important; }
        .stickers-grid { gap: 6px; }
        .sticker { box-shadow: none; }
    }
</style>
@endsection
