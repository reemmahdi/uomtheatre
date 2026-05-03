<div wire:poll.3s id="seatsDisplayRoot">

{{-- شريط التحكم --}}
<div class="control-bar">
    <div class="card-custom p-3 mb-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <h5 class="mb-0" style="color: var(--primary);">
                    <i class="bi bi-display"></i> شاشة عرض المقاعد
                </h5>
                <small class="text-muted">
                    <i class="bi bi-arrow-repeat"></i> تحديث تلقائي كل 3 ثوانٍ
                </small>
            </div>
            <div class="col-md-5">
                <select wire:model.live="selectedEventId" class="form-select">
                    <option value="">-- اختر فعالية --</option>
                    @foreach($events as $ev)
                    <option value="{{ $ev->id }}">
                        {{ $ev->title }} ({{ $ev->start_datetime->format('Y-m-d H:i') }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-end">
                @if($event)
                <span class="live-badge me-2">
                    <span class="pulse-dot"></span> مباشر
                </span>
                <button class="btn btn-fullscreen" onclick="enterFullscreen()" title="عرض ملء الشاشة للجمهور">
                    <i class="bi bi-arrows-fullscreen"></i> ملء الشاشة
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@if(!$event)
<div class="card-custom p-5 text-center">
    <i class="bi bi-display" style="font-size: 70px; color: #0369A1;"></i>
    <h4 class="mt-3" style="color: var(--primary);">اختر فعالية لعرض المقاعد</h4>
</div>
@else

{{-- ✨ شريط مدمج للجمهور (header + stats مع ساعة + إحصائيات) --}}
<div class="audience-bar">
    <div class="audience-bar-left">
        <img src="{{ asset('images/logo.png') }}" alt="جامعة الموصل" class="audience-logo-mini" onerror="this.style.display='none';">
        <div class="audience-info-mini">
            <div class="audience-event-mini">{{ $event->title }}</div>
            <div class="audience-uni-mini">جامعة الموصل</div>
        </div>
    </div>

    <div class="audience-bar-stats">
        <div class="bar-stat available-bar-stat">
            <i class="bi bi-check-circle-fill"></i>
            <span class="bar-stat-num">{{ $stats['available'] }}</span>
            <span class="bar-stat-lbl">متاح</span>
        </div>
        <div class="bar-stat booked-bar-stat">
            <i class="bi bi-x-circle-fill"></i>
            <span class="bar-stat-num">{{ $stats['booked'] }}</span>
            <span class="bar-stat-lbl">محجوز</span>
        </div>
        <div class="bar-stat vip-bar-stat">
            <i class="bi bi-star-fill"></i>
            <span class="bar-stat-num">{{ $stats['vip_booked'] }}/{{ $stats['vip_total'] }}</span>
            <span class="bar-stat-lbl">وفود</span>
        </div>
        <div class="bar-stat checked-bar-stat">
            <i class="bi bi-person-check-fill"></i>
            <span class="bar-stat-num">{{ $stats['checked_in'] }}</span>
            <span class="bar-stat-lbl">حضر</span>
        </div>
    </div>

    <div class="audience-bar-right">
        <div class="bar-clock">
            <div class="bar-clock-time" id="liveClock">--:--:--</div>
            <div class="bar-clock-date" id="liveDate">{{ $event->start_datetime->format('Y-m-d H:i') }}</div>
        </div>
    </div>
</div>

{{-- معلومات الفعالية - الوضع العادي --}}
<div class="event-info-bar control-bar">
    <div class="card-custom p-3 mb-3" style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1" style="color: #fff;">{{ $event->title }}</h5>
                <small style="color: #E4C05E;">
                    <i class="bi bi-clock"></i> {{ $event->start_datetime->format('Y-m-d H:i') }}
                </small>
            </div>
            <div class="d-flex gap-3 stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-num" style="color: #86efac;">{{ $stats['available'] }}</div>
                    <div class="stat-mini-lbl">متاح</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num" style="color: #fca5a5;">{{ $stats['booked'] }}</div>
                    <div class="stat-mini-lbl">محجوز</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num" style="color: #fcd34d;">{{ $stats['vip_booked'] }}/{{ $stats['vip_total'] }}</div>
                    <div class="stat-mini-lbl">وفود</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-num" style="color: #93c5fd;">{{ $stats['checked_in'] }}</div>
                    <div class="stat-mini-lbl">حضروا</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- المسرح والمقاعد --}}
<div class="theater-wrapper">

    <div class="stage-section">
        <div class="spotlights">
            <div class="spotlight spotlight-1"></div>
            <div class="spotlight spotlight-2"></div>
            <div class="spotlight spotlight-3"></div>
        </div>

        <div class="stage-realistic">
            <div class="curtain curtain-right">
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
            </div>

            <div class="curtain curtain-left">
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
                <div class="curtain-fold"></div>
            </div>

            <div class="stage-content">
                <div class="stage-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#E4C05E">
                        <path d="M12 1.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zM12 15c-3 0-9 1.5-9 4.5V22h18v-2.5c0-3-6-4.5-9-4.5z" opacity="0.3"/>
                        <circle cx="12" cy="6" r="3" fill="#E4C05E"/>
                        <path d="M5 22V18a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v4" stroke="#E4C05E" stroke-width="2" fill="none"/>
                    </svg>
                </div>
                <div class="stage-label">المسرح</div>
                <div class="stage-sublabel">STAGE</div>
            </div>

            <div class="stage-floor-lights"></div>
        </div>

        <div class="stage-floor"></div>
    </div>

    <div class="floor-section">
        <div class="section-title-wrapper">
            <span class="section-title floor-title">
                <i class="bi bi-grid-3x3-gap"></i> المنطقة الأرضية
            </span>
        </div>

        <div class="sections-grid">

            @if($sections['C'])
            <div class="section-block">
                <div class="section-name">قسم C</div>
                @foreach($sections['C'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($sections['B'])
            <div class="section-block">
                <div class="section-name">قسم B</div>
                @foreach($sections['B'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($sections['A'])
            <div class="section-block">
                <div class="section-name">قسم A</div>
                @foreach($sections['A'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    <div class="balcony-section">
        <div class="section-title-wrapper">
            <span class="section-title balcony-title">
                <i class="bi bi-star-fill"></i> الشرفة العلوية (VIP)
            </span>
        </div>

        <div class="sections-grid">

            @if($sections['F'])
            <div class="section-block balcony-block">
                <div class="section-name vip">قسم F</div>
                @foreach($sections['F'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($sections['E'])
            <div class="section-block balcony-block">
                <div class="section-name vip">قسم E</div>
                @foreach($sections['E'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($sections['D'])
            <div class="section-block balcony-block">
                <div class="section-name vip">قسم D</div>
                @foreach($sections['D'] as $row)
                <div class="seat-row">
                    <span class="row-num">{{ $row['number'] }}</span>
                    <div class="seats">
                        @foreach($row['seats'] as $seat)
                        <div class="seat seat-{{ $seat['status'] }}" title="{{ $seat['label'] }}@if($seat['guest_name']) - {{ $seat['guest_name'] }}@endif">
                            <div class="seat-back"></div>
                            <div class="seat-cushion"></div>
                            <div class="seat-armrest seat-armrest-left"></div>
                            <div class="seat-armrest seat-armrest-right"></div>
                            @if(in_array($seat['status'], ['checked_in', 'vip_checked_in']))
                            <div class="seat-check">✓</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>
</div>

{{-- دليل الألوان (الوضع العادي فقط) --}}
<div class="card-custom p-3 mt-3 control-bar">
    <div class="d-flex flex-wrap gap-3 justify-content-center small">
        <span class="legend-item">
            <span class="legend-seat seat-available">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span> متاح
        </span>
        <span class="legend-item">
            <span class="legend-seat seat-vip_available">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span> مقعد وفود
        </span>
        <span class="legend-item">
            <span class="legend-seat seat-booked">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span> محجوز
        </span>
        <span class="legend-item">
            <span class="legend-seat seat-vip_booked">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span> وفد محجوز
        </span>
        <span class="legend-item">
            <span class="legend-seat seat-checked_in">
                <div class="seat-back"></div>
                <div class="seat-cushion"></div>
            </span> ✓ حضر
        </span>
    </div>
</div>

{{-- زر الخروج --}}
<button class="exit-fullscreen-btn" onclick="exitFullscreen()" title="خروج من ملء الشاشة">
    <i class="bi bi-x-lg"></i>
</button>

@endif

<style>
    /* ═══════════════ الوضع العادي ═══════════════ */
    .live-badge {
        background: #16a34a; color: #fff;
        padding: 6px 14px; border-radius: 20px;
        font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
    }
    .pulse-dot {
        display: inline-block; width: 8px; height: 8px;
        background: #fff; border-radius: 50%; margin-left: 6px;
        animation: pulse-anim 1.5s ease-in-out infinite;
    }
    @keyframes pulse-anim {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.3); }
    }

    .stat-mini { text-align: center; padding: 4px 14px; border-right: 1px solid rgba(255,255,255,0.2); }
    .stat-mini:last-child { border-right: none; }
    .stat-mini-num { font-size: 22px; font-weight: 700; line-height: 1; }
    .stat-mini-lbl { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 4px; }

    .btn-fullscreen {
        background: linear-gradient(135deg, #E4C05E, #C9A445);
        color: #5a4500; border: none;
        padding: 8px 16px; border-radius: 20px;
        font-weight: 700; font-size: 13px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(228, 192, 94, 0.35);
        transition: all 0.2s;
    }
    .btn-fullscreen:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(228, 192, 94, 0.45);
    }

    .exit-fullscreen-btn {
        position: fixed;
        top: 16px;
        left: 16px;
        width: 38px;
        height: 38px;
        background: rgba(220, 38, 38, 0.9);
        color: #fff;
        border: 2px solid #fff;
        border-radius: 50%;
        font-size: 14px;
        cursor: pointer;
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        transition: all 0.2s;
    }
    .exit-fullscreen-btn:hover {
        background: rgba(185, 28, 28, 1);
        transform: scale(1.1);
    }

    /* ═══════════════ حاوية المسرح ═══════════════ */
    .theater-wrapper {
        background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 20px 15px 30px;
        overflow-x: auto;
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.03);
    }

    /* ═══════════════ المسرح ═══════════════ */
    .stage-section {
        max-width: 900px;
        margin: 0 auto 35px;
        position: relative;
    }

    .spotlights {
        position: relative;
        height: 30px;
        margin-bottom: -10px;
        z-index: 1;
    }

    .spotlight {
        position: absolute;
        width: 200px;
        height: 60px;
        top: 0;
        background: radial-gradient(ellipse at top, rgba(228, 192, 94, 0.4) 0%, transparent 70%);
        animation: spotlightFlicker 4s ease-in-out infinite;
    }
    .spotlight-1 { left: 10%; animation-delay: 0s; }
    .spotlight-2 { left: 50%; transform: translateX(-50%); animation-delay: 1s; }
    .spotlight-3 { right: 10%; animation-delay: 2s; }

    @keyframes spotlightFlicker {
        0%, 100% { opacity: 0.7; }
        50% { opacity: 1; }
    }

    .stage-realistic {
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        border-radius: 12px 12px 4px 4px;
        position: relative;
        height: 130px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.4), inset 0 -10px 20px rgba(0, 0, 0, 0.5);
        border: 2px solid #E4C05E;
    }

    .curtain {
        position: absolute;
        top: 0;
        height: 100%;
        width: 80px;
        display: flex;
        z-index: 2;
    }

    .curtain-right {
        right: 0;
        background: linear-gradient(90deg, #7f1d1d 0%, #991b1b 25%, #b91c1c 50%, #991b1b 75%, #7f1d1d 100%);
    }

    .curtain-left {
        left: 0;
        background: linear-gradient(270deg, #7f1d1d 0%, #991b1b 25%, #b91c1c 50%, #991b1b 75%, #7f1d1d 100%);
    }

    .curtain-fold {
        flex: 1;
        height: 100%;
        border-right: 1px solid rgba(0, 0, 0, 0.3);
        background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.15) 100%);
    }

    .curtain-right .curtain-fold:last-child { border-right: 3px solid #E4C05E; }
    .curtain-left .curtain-fold:first-child { border-left: 3px solid #E4C05E; border-right: none; }

    .stage-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        z-index: 1;
    }

    .stage-icon {
        margin-bottom: 6px;
        animation: iconGlow 3s ease-in-out infinite;
    }

    @keyframes iconGlow {
        0%, 100% { filter: drop-shadow(0 0 4px rgba(228, 192, 94, 0.5)); }
        50% { filter: drop-shadow(0 0 12px rgba(228, 192, 94, 0.9)); }
    }

    .stage-label {
        color: #E4C05E;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: 4px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        margin-bottom: 2px;
    }

    .stage-sublabel {
        color: #E4C05E;
        font-size: 11px;
        letter-spacing: 6px;
        opacity: 0.7;
    }

    .stage-floor-lights {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, transparent 0%, rgba(228, 192, 94, 0.6) 20%, rgba(228, 192, 94, 1) 50%, rgba(228, 192, 94, 0.6) 80%, transparent 100%);
        z-index: 3;
        box-shadow: 0 0 15px rgba(228, 192, 94, 0.6);
    }

    .stage-floor {
        height: 22px;
        background: repeating-linear-gradient(90deg, #78350f 0px, #92400e 4px, #78350f 8px), linear-gradient(180deg, #92400e 0%, #451a03 100%);
        border-radius: 0 0 8px 8px;
        position: relative;
        margin: 0 -5px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2), inset 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .stage-floor::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: rgba(0, 0, 0, 0.4);
    }

    /* ═══════════════ عناوين الأقسام ═══════════════ */
    .section-title-wrapper { text-align: center; margin-bottom: 18px; }
    .section-title {
        display: inline-block;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 22px;
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .floor-title { color: #0369A1; }
    .balcony-title { color: #7c3aed; }

    .balcony-section {
        margin-top: 30px;
        padding-top: 25px;
        border-top: 2px dashed #cbd5e1;
        position: relative;
    }

    .balcony-section::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 16px;
        height: 16px;
        background: #cbd5e1;
        border-radius: 50%;
    }

    /* ═══════════════ الأقسام ═══════════════ */
    .sections-grid {
        display: flex;
        gap: 25px;
        justify-content: center;
        flex-wrap: nowrap;
        min-width: fit-content;
    }

    .section-block {
        background: #fff;
        border-radius: 12px;
        padding: 16px 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
    }

    .balcony-block {
        background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
        border-color: #e9d5ff;
    }

    .section-name {
        background: linear-gradient(135deg, #0369A1, #0284C7);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        padding: 8px;
        border-radius: 8px;
        margin-bottom: 14px;
        letter-spacing: 1px;
        box-shadow: 0 2px 4px rgba(3, 105, 161, 0.2);
    }

    .section-name.vip {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);
    }

    /* صف المقاعد */
    .seat-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .row-num {
        font-size: 9px;
        color: #94a3b8;
        font-weight: 600;
        min-width: 16px;
        text-align: center;
        flex-shrink: 0;
    }

    .seats {
        display: flex;
        gap: 3px;
        flex-wrap: nowrap;
        justify-content: center;
        flex: 1;
    }

    /* ═══════════════ المقعد ═══════════════ */
    .seat {
        position: relative;
        width: 16px;
        height: 18px;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
        transform-origin: bottom center;
    }

    .seat .seat-back {
        position: absolute;
        top: 0;
        left: 2px;
        right: 2px;
        height: 9px;
        border-radius: 4px 4px 1px 1px;
        background-color: var(--seat-color);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), inset 0 -1px 0 rgba(0, 0, 0, 0.15);
    }

    .seat .seat-back::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 50%;
        transform: translateX(-50%);
        width: 60%;
        height: 1px;
        background: rgba(0, 0, 0, 0.2);
    }

    .seat .seat-cushion {
        position: absolute;
        bottom: 1px;
        left: 0;
        right: 0;
        height: 8px;
        border-radius: 2px 2px 4px 4px;
        background-color: var(--seat-color);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3), inset 0 -2px 2px rgba(0, 0, 0, 0.2), 0 1px 1px rgba(0, 0, 0, 0.15);
    }

    .seat .seat-armrest {
        position: absolute;
        bottom: 1px;
        width: 2px;
        height: 10px;
        background-color: var(--seat-color);
        filter: brightness(0.7);
        border-radius: 1px;
    }

    .seat .seat-armrest-left { left: 0; }
    .seat .seat-armrest-right { right: 0; }

    .seat .seat-check {
        position: absolute;
        top: 2px;
        left: 50%;
        transform: translateX(-50%);
        color: #fff;
        font-size: 7px;
        font-weight: 700;
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.6);
        z-index: 2;
    }

    /* ألوان المقاعد */
    .seat-available { --seat-color: #22c55e; }
    .seat-vip_available { --seat-color: #fbbf24; }
    .seat-vip_available .seat-back {
        background-color: #fbbf24;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), inset 0 -1px 0 rgba(180, 83, 9, 0.3), 0 0 4px rgba(251, 191, 36, 0.5);
    }
    .seat-booked { --seat-color: #ef4444; }
    .seat-vip_booked { --seat-color: #a855f7; }
    .seat-checked_in { --seat-color: #3b82f6; }
    .seat-vip_checked_in { --seat-color: #1e40af; }

    .seat:hover {
        transform: scale(1.6) translateY(-3px);
        z-index: 10;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
    }

    /* دليل الألوان */
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .legend-seat {
        position: relative;
        width: 22px;
        height: 24px;
        flex-shrink: 0;
    }

    .legend-seat .seat-back {
        position: absolute;
        top: 0;
        left: 3px;
        right: 3px;
        height: 12px;
        border-radius: 4px 4px 1px 1px;
        background-color: var(--seat-color);
    }

    .legend-seat .seat-cushion {
        position: absolute;
        bottom: 1px;
        left: 0;
        right: 0;
        height: 11px;
        border-radius: 2px 2px 4px 4px;
        background-color: var(--seat-color);
    }

    /* ═══════════════ Audience Bar (مخفي افتراضياً) ═══════════════ */
    .audience-bar {
        display: none;
    }

    /* ═══════════════════════════════════════════════════
       ✨ وضع ملء الشاشة - تحسين العرض الكامل ✨
       ═══════════════════════════════════════════════════ */
    body.fullscreen-mode {
        background: #0a0e1a;
        overflow: hidden;
        margin: 0;
        padding: 0;
    }

    /* إخفاء كل شيء غير ضروري */
    body.fullscreen-mode .sidebar,
    body.fullscreen-mode .top-bar,
    body.fullscreen-mode .control-bar,
    body.fullscreen-mode .event-info-bar {
        display: none !important;
    }

    body.fullscreen-mode .main-content {
        margin: 0 !important;
        padding: 0 !important;
        height: 100vh;
        width: 100vw;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    body.fullscreen-mode #seatsDisplayRoot {
        height: 100vh;
        width: 100vw;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* ═══════════════ Audience Bar - شريط واحد مدمج ═══════════════ */
    body.fullscreen-mode .audience-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 20px;
        background: linear-gradient(135deg, #0C4A6E 0%, #075985 100%);
        border-bottom: 2px solid #E4C05E;
        height: 70px;
        flex-shrink: 0;
        gap: 15px;
    }

    .audience-bar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 0 0 auto;
        min-width: 200px;
    }

    .audience-logo-mini {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid #E4C05E;
        background: rgba(255, 255, 255, 0.1);
        padding: 2px;
    }

    .audience-info-mini {
        display: flex;
        flex-direction: column;
    }

    .audience-event-mini {
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.2;
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .audience-uni-mini {
        color: #E4C05E;
        font-size: 11px;
        letter-spacing: 2px;
    }

    /* الإحصائيات في الشريط */
    .audience-bar-stats {
        display: flex;
        gap: 10px;
        flex: 1;
        justify-content: center;
        flex-wrap: nowrap;
    }

    .bar-stat {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        padding: 6px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(10px);
    }

    .bar-stat i {
        font-size: 18px;
    }

    .available-bar-stat i { color: #22c55e; }
    .booked-bar-stat i { color: #ef4444; }
    .vip-bar-stat i { color: #E4C05E; }
    .checked-bar-stat i { color: #3b82f6; }

    .bar-stat-num {
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }

    .bar-stat-lbl {
        color: #cbd5e1;
        font-size: 11px;
    }

    /* الساعة */
    .audience-bar-right {
        flex: 0 0 auto;
    }

    .bar-clock {
        background: rgba(228, 192, 94, 0.1);
        border: 1px solid rgba(228, 192, 94, 0.3);
        padding: 6px 14px;
        border-radius: 10px;
        text-align: center;
        backdrop-filter: blur(10px);
    }

    .bar-clock-time {
        color: #E4C05E;
        font-size: 18px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        line-height: 1;
        letter-spacing: 1px;
    }

    .bar-clock-date {
        color: #cbd5e1;
        font-size: 9px;
        margin-top: 2px;
    }

    /* ═══════════════════════════════════════════════════
       ✨ المسرح في وضع ملء الشاشة - يملأ الباقي
       ═══════════════════════════════════════════════════ */
    body.fullscreen-mode .theater-wrapper {
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        border-radius: 0;
        padding: 12px 20px;
        margin: 0;
        box-shadow: none;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        gap: 0;
    }

    /* ✨ المسرح أصغر */
    body.fullscreen-mode .stage-section {
        max-width: 700px;
        margin: 0 auto 8px;
        flex-shrink: 0;
    }

    body.fullscreen-mode .spotlights {
        height: 18px;
        margin-bottom: -8px;
    }

    body.fullscreen-mode .spotlight {
        width: 140px;
        height: 40px;
    }

    body.fullscreen-mode .stage-realistic {
        height: 75px;
    }

    body.fullscreen-mode .stage-label {
        font-size: 18px;
        margin-bottom: 0;
        letter-spacing: 3px;
    }

    body.fullscreen-mode .stage-sublabel {
        font-size: 9px;
    }

    body.fullscreen-mode .stage-icon {
        margin-bottom: 2px;
    }

    body.fullscreen-mode .stage-icon svg {
        width: 22px !important;
        height: 22px !important;
    }

    body.fullscreen-mode .curtain {
        width: 50px;
    }

    body.fullscreen-mode .stage-floor {
        height: 12px;
    }

    /* عناوين الأقسام أصغر */
    body.fullscreen-mode .section-title-wrapper {
        margin-bottom: 6px;
    }

    body.fullscreen-mode .section-title {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #E4C05E !important;
        font-size: 11px;
        padding: 4px 14px;
    }

    /* ✨ الأقسام تتسع تلقائياً */
    body.fullscreen-mode .floor-section,
    body.fullscreen-mode .balcony-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    body.fullscreen-mode .balcony-section {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed rgba(255, 255, 255, 0.2);
    }

    body.fullscreen-mode .balcony-section::before {
        background: rgba(228, 192, 94, 0.5);
        width: 12px;
        height: 12px;
        top: -6px;
    }

    body.fullscreen-mode .sections-grid {
        gap: 12px;
        flex: 1;
        align-items: stretch;
    }

    body.fullscreen-mode .section-block {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 8px 6px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    body.fullscreen-mode .balcony-block {
        background: rgba(124, 58, 237, 0.08);
        border-color: rgba(124, 58, 237, 0.2);
    }

    body.fullscreen-mode .section-name {
        font-size: 12px;
        padding: 4px 8px;
        margin-bottom: 6px;
        flex-shrink: 0;
    }

    /* ✨ المقاعد تتقلص لتملأ بالضبط */
    body.fullscreen-mode .seat-row {
        gap: 3px;
        margin-bottom: 2px;
        flex-shrink: 0;
    }

    body.fullscreen-mode .row-num {
        font-size: 7px;
        min-width: 12px;
    }

    body.fullscreen-mode .seats {
        gap: 1.5px;
    }

    /* ✨ المقعد بحجم صغير ليتسع الكل */
    body.fullscreen-mode .seat {
        width: 11px;
        height: 13px;
    }

    body.fullscreen-mode .seat .seat-back {
        height: 6px;
        left: 1px;
        right: 1px;
        border-radius: 2px 2px 1px 1px;
    }

    body.fullscreen-mode .seat .seat-back::before {
        top: 1px;
        height: 0.5px;
    }

    body.fullscreen-mode .seat .seat-cushion {
        height: 6px;
        bottom: 1px;
    }

    body.fullscreen-mode .seat .seat-armrest {
        width: 1.5px;
        height: 7px;
        bottom: 1px;
    }

    body.fullscreen-mode .seat .seat-check {
        font-size: 5px;
        top: 1px;
    }

    body.fullscreen-mode .seat:hover {
        transform: scale(2.5) translateY(-3px);
    }

    /* ═══════════════ Responsive للشاشات الكبيرة ═══════════════ */
    @media (min-width: 1920px) {
        body.fullscreen-mode .seat {
            width: 14px !important;
            height: 16px !important;
        }

        body.fullscreen-mode .seat .seat-back { height: 8px !important; }
        body.fullscreen-mode .seat .seat-cushion { height: 7px !important; }
        body.fullscreen-mode .seat .seat-armrest { height: 9px !important; }

        body.fullscreen-mode .stage-label { font-size: 22px; }
        body.fullscreen-mode .audience-event-mini { font-size: 18px; }
        body.fullscreen-mode .bar-stat-num { font-size: 22px; }
    }

    /* Responsive للجوال (الوضع العادي) */
    @media (max-width: 1100px) {
        .theater-wrapper { padding: 15px 10px 20px; }
        .sections-grid { gap: 15px; }
        .section-block { padding: 10px 8px; }
        .stage-realistic { height: 110px; }
        .curtain { width: 60px; }
        .stage-label { font-size: 22px; }
    }
</style>

@script
<script>
    window.enterFullscreen = function() {
        const elem = document.documentElement;
        document.body.classList.add('fullscreen-mode');

        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(err => console.log('Fullscreen failed:', err));
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }

        startClock();
    };

    window.exitFullscreen = function() {
        document.body.classList.remove('fullscreen-mode');

        if (document.fullscreenElement) {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }

        stopClock();
    };

    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            document.body.classList.remove('fullscreen-mode');
            stopClock();
        }
    });

    let clockInterval = null;

    function updateClock() {
        const now = new Date();
        const timeEl = document.getElementById('liveClock');
        const dateEl = document.getElementById('liveDate');

        if (timeEl) {
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${h}:${m}:${s}`;
        }

        if (dateEl) {
            const months = ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران',
                          'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'];
            const day = now.getDate();
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            dateEl.textContent = `${day} ${month} ${year}`;
        }
    }

    function startClock() {
        updateClock();
        clockInterval = setInterval(updateClock, 1000);
    }

    function stopClock() {
        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }
    }
</script>
@endscript

</div>
