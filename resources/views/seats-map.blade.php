@php
    use App\Models\Event;
    use App\Models\Status;

    $availableStatusIds = Status::whereIn('name', ['added', 'active', 'published'])->pluck('id');
    $events = Event::whereIn('status_id', $availableStatusIds)
        ->orderBy('start_datetime', 'desc')
        ->get();

    $selectedEventId = request()->query('event_id');
    $selectedEvent = $selectedEventId ? Event::find($selectedEventId) : ($events->first() ?? null);
    if (!$selectedEventId && $selectedEvent) {
        $selectedEventId = $selectedEvent->id;
    }
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title>خارطة مقاعد مسرح جامعة الموصل</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/seats-map.css') }}">





</head>
<body>

<div class="event-selector">
  <label for="eventSelect">
    <i class="bi bi-calendar-event"></i>
    اختر الفعالية:
  </label>
  <select id="eventSelect" onchange="if(this.value) window.location.href='?event_id='+this.value;">
    <option value="">-- اختر فعالية --</option>
    @foreach($events as $evt)
      @php
        $evtDt = $evt->start_datetime ? \Carbon\Carbon::parse($evt->start_datetime) : null;
        $evtTimeText = '';
        if ($evtDt) {
            $evtHour12 = $evtDt->format('g');
            $evtMin    = $evtDt->format('i');
            $evtPeriod = $evtDt->format('A') === 'AM' ? 'صباحاً' : 'مساءً';
            $evtDate   = $evtDt->format('Y-m-d');
            $evtTimeText = " ({$evtDate} - {$evtHour12}:{$evtMin} {$evtPeriod})";
        }
@endphp
      <option value="{{ $evt->id }}" {{ $selectedEventId == $evt->id ? 'selected' : '' }}>
        {{ $evt->title }}{{ $evtTimeText }}
      </option>
    @endforeach
  </select>

  @if($selectedEvent)
    <span class="event-info-pill">
      <i class="bi bi-check-circle-fill"></i>
      {{ $selectedEvent->title }}
    </span>
  @endif
</div>

@if($selectedEventId)
  <script>window.SELECTED_EVENT_ID = {{ $selectedEventId }};</script>
@else
  <script>window.SELECTED_EVENT_ID = null;</script>
@endif

<div class="app">

  <!-- ============== TOP BAR ============== -->
  <header class="topbar">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <path d="M3 21V10l9-6 9 6v11" /><path d="M9 21v-7h6v7" />
        </svg>
      </div>
      <div class="brand-text">
        <div class="brand-title">مسرح جامعة الموصل</div>
        <div class="brand-sub">University of Mosul Theater</div>
      </div>
    </div>

    <div class="event-meta">
      @if($selectedEvent && $selectedEvent->start_datetime)
        @php
          $arabicDays = [
              'Saturday'  => 'السبت',
              'Sunday'    => 'الأحد',
              'Monday'    => 'الإثنين',
              'Tuesday'   => 'الثلاثاء',
              'Wednesday' => 'الأربعاء',
              'Thursday'  => 'الخميس',
              'Friday'    => 'الجمعة',
          ];
          $arabicMonths = [
              1  => 'كانون الثاني',
              2  => 'شباط',
              3  => 'آذار',
              4  => 'نيسان',
              5  => 'أيار',
              6  => 'حزيران',
              7  => 'تموز',
              8  => 'آب',
              9  => 'أيلول',
              10 => 'تشرين الأول',
              11 => 'تشرين الثاني',
              12 => 'كانون الأول',
          ];

          $startDt = \Carbon\Carbon::parse($selectedEvent->start_datetime);
          $dayName = $arabicDays[$startDt->format('l')] ?? '';
          $dayNum  = $startDt->format('j');
          $month   = $arabicMonths[(int)$startDt->format('n')] ?? '';
          $year    = $startDt->format('Y');

          $hour12  = $startDt->format('g');
          $minute  = $startDt->format('i');
          $period  = $startDt->format('A') === 'AM' ? 'صباحاً' : 'مساءً';
@endphp
        <span class="pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          {{ $dayName }} {{ $dayNum }} {{ $month }} {{ $year }}
        </span>
        <span class="pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          {{ $hour12 }}:{{ $minute }} {{ $period }}
        </span>
      @else
        <span class="pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          لم تُختر فعالية بعد
        </span>
      @endif
      <span class="pill">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
        قاعة محمود الجليلي
      </span>
    </div>

    <div class="topbar-actions">
      <button class="icon-btn" id="fullscreenBtn" title="ملء الشاشة">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3M21 8V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3M16 21h3a2 2 0 002-2v-3"/></svg>
      </button>
      <button class="icon-btn" id="resetBtn" title="إعادة العرض">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9"/><path d="M3 4v5h5"/></svg>
      </button>
    </div>
  </header>

  <!-- ============== MAP AREA ============== -->
  <main class="map-area">

    <!-- Floor toggle -->
    <div class="floor-toggle" role="tablist">
      <button class="active" data-floor="all">عرض كامل</button>
      <button data-floor="orchestra">الطابق الأرضي</button>
      <button data-floor="balcony">الشرفة</button>
    </div>

    <!-- Legend -->
    <div class="legend" aria-label="مفتاح الألوان">
      <div class="legend-title">دليل الحالات</div>
      <div class="legend-row"><div class="left"><span class="legend-swatch av"></span><span>متاح</span></div></div>
      <div class="legend-row"><div class="left"><span class="legend-swatch sl"></span><span>مُحدَّد</span></div></div>
      <div class="legend-row"><div class="left"><span class="legend-swatch vp"></span><span>VIP / مقاعد الوفود</span></div></div>
      <div class="legend-row"><div class="left"><span class="legend-swatch rs"></span><span>محجوز</span></div></div>
      <div class="legend-row"><div class="left"><span class="legend-swatch sd"></span><span>مشغول</span></div></div>
    </div>

    <!-- Map canvas -->
    <div class="map-canvas" id="mapCanvas">
      <svg id="mapSvg" class="map-svg" viewBox="-450 50 2600 1500" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="stageGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1E3A5F"/>
            <stop offset="50%" stop-color="#0C4A6E"/>
            <stop offset="100%" stop-color="#082F49"/>
          </linearGradient>
          <linearGradient id="stageEdgeGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#E4C05E" stop-opacity="0"/>
            <stop offset="100%" stop-color="#E4C05E" stop-opacity="0.6"/>
          </linearGradient>
          <radialGradient id="stageLight" cx="50%" cy="0%" r="60%">
            <stop offset="0%" stop-color="#FCD981" stop-opacity="0.25"/>
            <stop offset="40%" stop-color="#E4C05E" stop-opacity="0.08"/>
            <stop offset="100%" stop-color="#E4C05E" stop-opacity="0"/>
          </radialGradient>
          <linearGradient id="curtainGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#7F1D1D"/>
            <stop offset="35%" stop-color="#B91C1C"/>
            <stop offset="55%" stop-color="#7F1D1D"/>
            <stop offset="80%" stop-color="#B91C1C"/>
            <stop offset="100%" stop-color="#450A0A"/>
          </linearGradient>
          <radialGradient id="floorGlow" cx="50%" cy="0%" r="80%">
            <stop offset="0%" stop-color="rgba(228,192,94,0.18)"/>
            <stop offset="60%" stop-color="rgba(228,192,94,0)"/>
          </radialGradient>
          <pattern id="curtainFolds" width="14" height="14" patternUnits="userSpaceOnUse">
            <path d="M0 0L0 14" stroke="rgba(0,0,0,0.18)" stroke-width="1"/>
            <path d="M7 0L7 14" stroke="rgba(255,255,255,0.05)" stroke-width="2"/>
          </pattern>
          <filter id="seatGlow" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur stdDeviation="2" result="blur"/>
            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
        </defs>

        <!-- Floor glow behind seats -->
        <rect x="-350" y="50" width="2400" height="1500" fill="url(#floorGlow)"/>

        <!-- Soft stage light spilling onto floor -->
        <ellipse cx="850" cy="320" rx="800" ry="240" fill="url(#stageLight)" pointer-events="none"/>

        <!-- Stage area at top - architectural, seat.io-style -->
        <g class="stage-reveal">
          <!-- Proscenium frame: rectangle with subtle inset -->
          <rect x="200" y="160" width="1300" height="120" rx="6"
                fill="url(#stageGrad)" stroke="#0C4A6E" stroke-width="1.5"/>
          <!-- Inner highlight stripe for depth -->
          <rect x="208" y="168" width="1284" height="2" fill="rgba(255,255,255,0.18)"/>
          <!-- Bottom gold accent edge -->
          <rect x="200" y="276" width="1300" height="4" fill="url(#stageEdgeGrad)"/>
          <!-- Stage apron (front edge curve hint) -->
          <path d="M 240 280 Q 850 320 1460 280" fill="none" stroke="rgba(228,192,94,0.55)" stroke-width="2.5"/>
          <path d="M 240 280 Q 850 320 1460 280 L 1450 295 Q 850 332 250 295 Z"
                fill="rgba(12,74,110,0.12)"/>

          <!-- Footlights: row of small dots along front edge -->
          <g opacity="0.9">
            <circle cx="320" cy="290" r="3.2" fill="#FCD981"/>
            <circle cx="450" cy="298" r="3.2" fill="#FCD981"/>
            <circle cx="580" cy="306" r="3.2" fill="#FCD981"/>
            <circle cx="715" cy="312" r="3.2" fill="#FCD981"/>
            <circle cx="850" cy="314" r="3.5" fill="#FCD981"/>
            <circle cx="985" cy="312" r="3.2" fill="#FCD981"/>
            <circle cx="1120" cy="306" r="3.2" fill="#FCD981"/>
            <circle cx="1250" cy="298" r="3.2" fill="#FCD981"/>
            <circle cx="1380" cy="290" r="3.2" fill="#FCD981"/>
          </g>

          <!-- Stage label, centered & refined -->
          <text x="850" y="218" class="stage-text">خشبة المسرح</text>
          <text x="850" y="248" class="stage-text-en">· STAGE ·</text>

          <!-- Decorative gold corner brackets -->
          <path d="M 215 175 L 215 165 L 230 165" stroke="#E4C05E" stroke-width="1.5" fill="none"/>
          <path d="M 1485 175 L 1485 165 L 1470 165" stroke="#E4C05E" stroke-width="1.5" fill="none"/>
          <path d="M 215 265 L 215 275 L 230 275" stroke="#E4C05E" stroke-width="1.5" fill="none"/>
          <path d="M 1485 265 L 1485 275 L 1470 275" stroke="#E4C05E" stroke-width="1.5" fill="none"/>
        </g>

        <!-- Seats group (populated by JS) -->
        <g id="seatsGroup" class="seats-fade-in"></g>

        <!-- Section labels (populated by JS) -->
        <g id="labelsGroup"></g>

        <!-- Balcony divider -->
        <g id="balconyDivider"></g>
      </svg>
    </div>

    <!-- Tooltip -->
    <div class="tooltip" id="tooltip" role="tooltip"></div>

    <!-- Zoom controls -->
    <div class="zoom-controls">
      <button id="zoomIn" title="تكبير">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
      </button>
      <button id="zoomOut" title="تصغير">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35M8 11h6"/></svg>
      </button>
      <div class="zoom-divider"></div>
      <button id="zoomReset" title="إعادة">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12c0-5-4-9-9-9-4 0-7 2-8.6 5"/><path d="M3 4v5h5"/><path d="M3 12c0 5 4 9 9 9 4 0 7-2 8.6-5"/><path d="M21 20v-5h-5"/></svg>
      </button>
      <div class="zoom-level" id="zoomLevel">100%</div>
    </div>

    <!-- Minimap (collapsible) -->
    <div class="minimap" id="minimap">
      <button class="minimap-toggle" id="minimapToggle" title="إخفاء/إظهار الخريطة المصغّرة">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      <span class="minimap-label">خريطة مصغّرة</span>
      <span class="minimap-collapsed-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      </span>
      <svg class="minimap-svg" viewBox="-450 50 2600 1500" preserveAspectRatio="xMidYMid meet">
        <use href="#seatsGroup-mini" />
        <rect class="minimap-viewport" id="minimapViewport" x="0" y="0" width="1600" height="1100"/>
      </svg>
    </div>

  </main>

  <!-- ============== SIDE PANEL ============== -->
  <aside class="side-panel">

    <div class="panel-section">
      <div class="event-card">
        <h2 class="event-card-title">حفل التخرّج السنوي ٢٠٢٦</h2>
        <p class="event-card-sub">كلية الهندسة - جامعة الموصل</p>
        <div class="event-card-meta">
          <div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            ٩٩٧ مقعد
          </div>
          <div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            ١٢٠ دقيقة
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing tiers -->
    <div class="panel-section">
      <div class="panel-title">
        <h3>أنواع المقاعد</h3>
      </div>
      <div class="tier">
        <div class="tier-left">
          <span class="tier-swatch" style="background:var(--gold);border:1.5px solid var(--gold-dark)"></span>
          <div>
            <div class="tier-name">VIP — مقاعد الوفود</div>
            <div class="tier-sub">صف ١٠ - الأقسام الأرضية</div>
          </div>
        </div>
      </div>
      <div class="tier">
        <div class="tier-left">
          <span class="tier-swatch" style="background:var(--primary-light)"></span>
          <div>
            <div class="tier-name">الشرفة العلوية</div>
            <div class="tier-sub">أقسام D · E · F</div>
          </div>
        </div>
      </div>
      <div class="tier">
        <div class="tier-left">
          <span class="tier-swatch" style="background:var(--available)"></span>
          <div>
            <div class="tier-name">الطابق الأرضي</div>
            <div class="tier-sub">أقسام A · B · C</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Best available -->
    <div class="panel-section">
      <div class="panel-title">
        <h3>أفضل المقاعد المتاحة</h3>
      </div>
      <div class="ba-controls">
        <div class="ba-counter">
          <button id="baMinus" aria-label="نقصان">−</button>
          <span class="num" id="baCount"><small>عدد المقاعد:</small>2</span>
          <button id="baPlus" aria-label="زيادة">+</button>
        </div>
      </div>
      <button class="btn btn-ghost" id="bestAvailableBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2l2.4 7.2H22l-6 4.8 2.4 7.2L12 17l-6.4 4.2L8 14.2l-6-4.8h7.6z"/></svg>
        اقترح أفضل المقاعد
      </button>
    </div>

    <!-- Selected seats -->
    <div class="panel-section" style="flex:1; overflow:hidden; display:flex; flex-direction:column; padding:16px 0 0;">
      <div class="panel-title" style="padding: 0 22px;">
        <h3>المقاعد المُختارة</h3>
        <span class="count" id="selectedCount">٠ مقاعد</span>
      </div>
      <div class="selected-list" id="selectedList">
        <div class="selected-empty" id="emptyState">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <div>لم تختر أي مقعد بعد.<br/>انقر على المقاعد الخضراء في الخارطة.</div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="actions">
      <div class="total-row">
        <span class="total-label">عدد المقاعد المُختارة</span>
        <span class="total-value"><span id="totalPrice">٠</span></span>
      </div>
      <button class="btn btn-primary" id="checkoutBtn" disabled>
        متابعة الحجز
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      </button>
    </div>

  </aside>

</div>

<div class="toast" id="toast"></div>

<!--
  ──────────────────────────────────────────────────────────────────────
  SEATING DATA STRUCTURE (for Laravel integration later)
  ──────────────────────────────────────────────────────────────────────

  Each seat object:
  {
    id:        "A-10-5"      // section-row-seat
    section:   "A"            // A,B,C orchestra · D,E,F balcony
    row:       10
    seat:      5
    floor:     "orchestra" | "balcony"
    status:    "available" | "reserved" | "sold"
    type:      "standard" | "vip"
    price:     25000
    x, y:      number  // computed coords on the SVG
  }

  Laravel integration:
    GET  /api/event/{eventId}/seats   → returns seats[] with current status
    POST /api/event/{eventId}/hold    → body: {seat_ids: []}  (lock for 10 min)
    POST /api/event/{eventId}/book    → finalize payment
    Use Laravel Echo + Pusher to broadcast `seat.updated` events
    so other users see seats turn red in real-time.
  ──────────────────────────────────────────────────────────────────────
-->


<script src="{{ asset('js/seats-map.js') }}"></script>

</body>
</html>
