@php
    use App\Models\Event;
    use App\Models\Status;
    
    // ✨ جلب الفعاليات المتاحة للعرض في الخارطة
    // الحالات: مضافة، نشطة، منشورة (بعد حذف under_review)
    $availableStatusIds = Status::whereIn('name', ['added', 'active', 'published'])->pluck('id');
    $events = Event::whereIn('status_id', $availableStatusIds)
        ->orderBy('start_datetime', 'desc')
        ->get();
    
    // الفعالية المختارة من URL
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
<style>
  :root {
    --primary:        #0C4A6E;
    --primary-mid:    #075985;
    --primary-light:  #0369A1;
    --primary-soft:   #E0F2FE;
    --gold:           #E4C05E;
    --gold-dark:      #C9A445;
    --gold-soft:      #FBF3D6;
    --available:      #22C55E;
    --available-dark: #16A34A;
    --reserved:       #94A3B8;
    --reserved-dark:  #64748B;
    --sold:           #EF4444;
    --sold-dark:      #B91C1C;
    --selected:       #0369A1;
    --selected-glow:  rgba(3,105,161,0.45);
    --bg:             #F8FAFC;
    --bg-alt:         #EEF2F7;
    --surface:        #FFFFFF;
    --ink:            #0F172A;
    --ink-2:          #334155;
    --ink-3:          #64748B;
    --line:           #E2E8F0;
    --line-2:         #CBD5E1;
    --shadow-sm: 0 1px 2px rgba(15,23,42,0.06), 0 1px 1px rgba(15,23,42,0.04);
    --shadow-md: 0 4px 12px rgba(15,23,42,0.08), 0 2px 4px rgba(15,23,42,0.04);
    --shadow-lg: 0 18px 40px rgba(15,23,42,0.12), 0 6px 14px rgba(15,23,42,0.06);
    --radius-sm: 8px;
    --radius-md: 14px;
    --radius-lg: 22px;
  }
  * { box-sizing: border-box; }
  html, body { margin:0; padding:0; height:100%; }
  body {
    font-family: 'Cairo', 'Tajawal', system-ui, sans-serif;
    background: var(--bg);
    color: var(--ink);
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
  }

  /* ---------- Layout ---------- */
  .app {
    display: grid;
    grid-template-columns: 1fr;
    grid-template-rows: 64px 1fr;
    min-height: 800px;
    background: var(--bg);
  }
  .map-area {
    min-height: 700px;
  }
  header.topbar {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: var(--surface);
    border-bottom: 1px solid var(--line);
    z-index: 30;
  }
  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .brand-mark {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    display: grid; place-items: center;
    color: var(--gold);
    box-shadow: var(--shadow-sm);
  }
  .brand-mark svg { width: 20px; height: 20px; }
  .brand-text { line-height: 1.15; }
  .brand-title { font-weight: 800; font-size: 15px; color: var(--ink); }
  .brand-sub { font-size: 12px; color: var(--ink-3); font-family: 'Tajawal', sans-serif; }

  .event-meta {
    display: flex; align-items: center; gap: 18px;
    font-family: 'Tajawal', sans-serif;
  }
  .event-meta .pill {
    padding: 6px 12px;
    background: var(--bg-alt);
    border-radius: 999px;
    font-size: 12.5px;
    color: var(--ink-2);
    display: inline-flex; gap: 8px; align-items: center;
  }
  .event-meta .pill svg { width: 14px; height: 14px; opacity: 0.7; }

  .topbar-actions { display: flex; gap: 8px; }
  .icon-btn {
    width: 36px; height: 36px;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: var(--surface);
    cursor: pointer;
    display: grid; place-items: center;
    color: var(--ink-2);
    transition: all .15s ease;
  }
  .icon-btn:hover { background: var(--bg-alt); color: var(--ink); border-color: var(--line-2); }
  .icon-btn svg { width: 16px; height: 16px; }

  /* ---------- Map area ---------- */
  .map-area {
    position: relative;
    overflow: hidden;
    background:
      radial-gradient(ellipse 80% 60% at 50% 0%, #FFFFFF 0%, transparent 70%),
      radial-gradient(ellipse 100% 100% at 50% 100%, #E8EFF7 0%, var(--bg) 50%),
      var(--bg);
  }
  .map-area::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
      radial-gradient(ellipse 30% 18% at 50% 18%, rgba(228,192,94,0.10) 0%, transparent 70%);
    pointer-events: none;
    z-index: 1;
  }
  .map-canvas {
    position: absolute;
    inset: 0;
    cursor: grab;
    user-select: none;
    touch-action: none;
  }
  .map-canvas.dragging { cursor: grabbing; }
  .map-svg {
    width: 100%;
    height: 100%;
    display: block;
  }

  /* ---------- Floor toggle ---------- */
  .floor-toggle {
    position: absolute;
    top: 18px;
    right: 18px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 4px;
    display: flex;
    box-shadow: var(--shadow-md);
    z-index: 10;
  }
  .floor-toggle button {
    border: 0; background: transparent;
    padding: 8px 16px;
    font-family: 'Cairo', sans-serif;
    font-weight: 600;
    font-size: 13px;
    color: var(--ink-3);
    border-radius: 9px;
    cursor: pointer;
    transition: all .18s ease;
  }
  .floor-toggle button.active {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 2px 6px rgba(12,74,110,0.25);
  }
  .floor-toggle button:not(.active):hover { color: var(--ink); }

  /* ---------- Legend ---------- */
  .legend {
    position: absolute;
    top: 18px;
    left: 18px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    padding: 14px 16px;
    box-shadow: var(--shadow-md);
    display: flex;
    flex-direction: column;
    gap: 9px;
    z-index: 10;
    min-width: 200px;
    backdrop-filter: blur(8px);
  }
  .legend-title {
    font-size: 11.5px;
    color: var(--ink-3);
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-family: 'Tajawal', sans-serif;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--line);
  }
  .legend-row {
    display: flex; align-items: center; gap: 10px;
    font-size: 12.5px;
    color: var(--ink-2);
    justify-content: space-between;
  }
  .legend-row .left { display:flex; align-items:center; gap:10px; }
  .legend-row .price {
    font-family: 'Tajawal', sans-serif;
    font-size: 11.5px;
    color: var(--ink-3);
    font-weight: 600;
  }
  .legend-swatch {
    width: 14px; height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
  }
  .legend-swatch.av { background: var(--available); }
  .legend-swatch.rs { background: var(--reserved); }
  .legend-swatch.sd { background: var(--sold); }
  .legend-swatch.vp { background: var(--gold); border: 1.5px solid var(--gold-dark); }
  .legend-swatch.sl { background: var(--selected); box-shadow: 0 0 0 2px var(--selected-glow); }

  /* ---------- Zoom controls ---------- */
  .zoom-controls {
    position: absolute;
    bottom: 22px;
    left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 4px;
    box-shadow: var(--shadow-md);
    z-index: 10;
  }
  .zoom-controls button {
    width: 36px; height: 36px;
    border: 0;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    color: var(--ink-2);
    display: grid; place-items: center;
    transition: background .15s ease;
  }
  .zoom-controls button:hover { background: var(--bg-alt); color: var(--ink); }
  .zoom-controls button svg { width: 16px; height: 16px; }
  .zoom-divider { height: 1px; background: var(--line); margin: 2px 4px; }
  .zoom-level {
    font-size: 11px;
    text-align: center;
    color: var(--ink-3);
    font-family: 'Tajawal', sans-serif;
    padding: 2px 0 4px;
  }

  /* ---------- Minimap ---------- */
  .minimap {
    position: absolute;
    bottom: 22px;
    right: 18px;
    width: 180px;
    height: 116px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    z-index: 10;
    transition: width .25s cubic-bezier(.65,.05,.36,1),
                height .25s cubic-bezier(.65,.05,.36,1),
                opacity .2s ease;
    opacity: 0.94;
  }
  .minimap:hover { opacity: 1; }
  .minimap.collapsed {
    width: 36px;
    height: 36px;
    cursor: pointer;
  }
  .minimap.collapsed .minimap-svg,
  .minimap.collapsed .minimap-label { display: none; }
  .minimap.collapsed .minimap-toggle { transform: rotate(180deg); }
  .minimap-svg { width: 100%; height: 100%; display: block; }
  .minimap-viewport {
    fill: rgba(3,105,161,0.12);
    stroke: var(--primary-light);
    stroke-width: 1.5;
    pointer-events: none;
  }
  .minimap-label {
    position: absolute;
    top: 6px; right: 8px;
    font-size: 10px;
    color: var(--ink-3);
    font-family: 'Tajawal', sans-serif;
    background: rgba(255,255,255,0.85);
    padding: 1px 6px;
    border-radius: 4px;
    pointer-events: none;
  }
  .minimap-toggle {
    position: absolute;
    top: 4px; left: 4px;
    width: 28px; height: 28px;
    border: 0;
    background: rgba(255,255,255,0.92);
    border-radius: 8px;
    cursor: pointer;
    display: grid;
    place-items: center;
    color: var(--ink-2);
    z-index: 2;
    transition: transform .25s ease, background .15s ease;
  }
  .minimap-toggle:hover { background: var(--bg-alt); color: var(--ink); }
  .minimap-toggle svg { width: 14px; height: 14px; }
  .minimap.collapsed .minimap-toggle {
    top: 4px; left: 4px;
    background: var(--surface);
  }
  .minimap.collapsed .minimap-collapsed-icon {
    display: grid;
  }
  .minimap-collapsed-icon {
    display: none;
    position: absolute;
    inset: 0;
    place-items: center;
    color: var(--primary);
    pointer-events: none;
  }
  .minimap-collapsed-icon svg { width: 18px; height: 18px; }

  /* ---------- Tooltip ---------- */
  .tooltip {
    position: absolute;
    pointer-events: none;
    background: var(--ink);
    color: #fff;
    padding: 9px 12px;
    border-radius: 10px;
    font-size: 12.5px;
    font-family: 'Tajawal', sans-serif;
    box-shadow: 0 8px 22px rgba(0,0,0,0.25);
    transform: translate(-50%, calc(-100% - 12px));
    opacity: 0;
    transition: opacity .12s ease;
    white-space: nowrap;
    z-index: 100;
    line-height: 1.5;
  }
  .tooltip.visible { opacity: 1; }
  .tooltip::after {
    content: '';
    position: absolute;
    bottom: -4px; left: 50%;
    width: 8px; height: 8px;
    background: var(--ink);
    transform: translateX(-50%) rotate(45deg);
  }
  .tt-title { font-weight: 700; font-size: 13.5px; font-family: 'Cairo', sans-serif; }
  .tt-row { display: flex; gap: 6px; align-items: center; opacity: 0.85; font-size: 11.5px; }
  .tt-row .dot { width: 8px; height: 8px; border-radius: 50%; }

  /* ---------- Side panel (checkout) ---------- */
  aside.side-panel {
    background: var(--surface);
    border-right: 1px solid var(--line);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
  }
  .panel-section {
    padding: 20px 22px;
    border-bottom: 1px solid var(--line);
  }
  .panel-section:last-child { border-bottom: 0; }

  .event-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
    color: #fff;
    border-radius: var(--radius-md);
    padding: 18px;
    position: relative;
    overflow: hidden;
  }
  .event-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(circle at 90% 10%, rgba(228,192,94,0.25), transparent 50%),
      radial-gradient(circle at 10% 90%, rgba(3,105,161,0.4), transparent 50%);
    pointer-events: none;
  }
  .event-card-title {
    font-weight: 800;
    font-size: 18px;
    margin: 0 0 6px;
    position: relative;
  }
  .event-card-sub {
    font-family: 'Tajawal', sans-serif;
    font-size: 13px;
    opacity: 0.85;
    margin: 0 0 14px;
    position: relative;
  }
  .event-card-meta {
    display: flex; gap: 14px;
    font-size: 12px;
    font-family: 'Tajawal', sans-serif;
    position: relative;
  }
  .event-card-meta div { display: flex; gap: 6px; align-items: center; opacity: 0.95; }
  .event-card-meta svg { width: 14px; height: 14px; }

  /* Pricing tier list */
  .tier {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed var(--line);
    font-family: 'Tajawal', sans-serif;
  }
  .tier:last-child { border-bottom: 0; }
  .tier-left { display: flex; align-items: center; gap: 10px; }
  .tier-swatch { width: 12px; height: 12px; border-radius: 3px; }
  .tier-name { font-size: 13px; color: var(--ink-2); }
  .tier-sub { font-size: 11px; color: var(--ink-3); }
  .tier-price { font-weight: 700; font-size: 14px; color: var(--ink); font-family: 'Cairo', sans-serif; }

  /* Selected seats list */
  .panel-title {
    display: flex; justify-content: space-between; align-items: baseline;
    margin: 0 0 12px;
  }
  .panel-title h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--ink);
  }
  .panel-title .count {
    font-size: 12px;
    color: var(--ink-3);
    font-family: 'Tajawal', sans-serif;
  }
  .selected-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 22px 12px;
  }
  .selected-empty {
    padding: 30px 12px;
    text-align: center;
    color: var(--ink-3);
    font-family: 'Tajawal', sans-serif;
    font-size: 13px;
  }
  .selected-empty svg { width: 56px; height: 56px; opacity: 0.35; margin-bottom: 10px; }
  .selected-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: var(--bg-alt);
    border-radius: 10px;
    margin-bottom: 8px;
    font-family: 'Tajawal', sans-serif;
    animation: slideIn .25s ease;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .selected-row .seat-id {
    font-weight: 700;
    color: var(--ink);
    font-family: 'Cairo', sans-serif;
    font-size: 13.5px;
  }
  .selected-row .seat-meta {
    font-size: 11.5px;
    color: var(--ink-3);
  }
  .selected-row .seat-price {
    font-weight: 700;
    color: var(--ink);
    font-family: 'Cairo', sans-serif;
    font-size: 13px;
  }
  .selected-row .remove-btn {
    border: 0; background: transparent;
    color: var(--ink-3);
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    margin-right: 4px;
    display: grid; place-items: center;
  }
  .selected-row .remove-btn:hover { background: rgba(239,68,68,0.12); color: var(--sold); }

  /* Action buttons */
  .actions { padding: 16px 22px 18px; border-top: 1px solid var(--line); background: #FAFBFC; }
  .total-row {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 12px;
  }
  .total-label { font-size: 13px; color: var(--ink-3); font-family: 'Tajawal', sans-serif; }
  .total-value {
    font-weight: 800;
    font-size: 22px;
    color: var(--ink);
    font-feature-settings: 'tnum';
  }
  .total-value .currency { font-size: 13px; color: var(--ink-3); font-weight: 600; margin-right: 4px; }
  .btn {
    width: 100%;
    padding: 13px 16px;
    border-radius: 12px;
    border: 0;
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all .15s ease;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .btn-primary {
    background: linear-gradient(180deg, var(--primary-light) 0%, var(--primary) 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(12,74,110,0.25);
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(12,74,110,0.32); }
  .btn-primary:disabled {
    background: var(--line-2);
    color: var(--ink-3);
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
  }
  .btn-ghost {
    background: var(--surface);
    border: 1px solid var(--line);
    color: var(--ink-2);
    margin-bottom: 8px;
  }
  .btn-ghost:hover { background: var(--bg-alt); }
  .btn-ghost svg { width: 16px; height: 16px; color: var(--gold-dark); }

  /* ---------- Best-available picker ---------- */
  .ba-controls { display: flex; gap: 8px; margin-bottom: 8px; }
  .ba-counter {
    flex: 1;
    display: flex; align-items: center; justify-content: space-between;
    background: var(--bg-alt);
    border-radius: 10px;
    padding: 4px;
  }
  .ba-counter button {
    width: 32px; height: 32px;
    border: 0; background: var(--surface);
    border-radius: 8px;
    cursor: pointer;
    color: var(--ink-2);
    font-weight: 700;
    box-shadow: var(--shadow-sm);
  }
  .ba-counter button:hover { color: var(--primary); }
  .ba-counter .num {
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    color: var(--ink);
  }
  .ba-counter .num small { color: var(--ink-3); font-weight: 500; font-size: 11px; margin-right: 6px; }

  /* ---------- Seat element ---------- */
  .seat {
    cursor: pointer;
    transition: transform .18s cubic-bezier(.34,1.56,.64,1), filter .15s ease;
    transform-origin: center;
            transform-box: fill-box;
    opacity: 0;
    animation: seatIn .55s cubic-bezier(.22,.9,.32,1) forwards;
  }
  @keyframes seatIn {
    from { opacity: 0; transform: scale(0.4); }
    to   { opacity: 1; transform: scale(1); }
  }
  .seat:hover { transform: scale(1.18); filter: drop-shadow(0 2px 6px rgba(15,23,42,0.25)); }
  .seat.selected {
    transform: scale(1.18);
    filter: drop-shadow(0 0 0 var(--selected)) drop-shadow(0 4px 10px var(--selected-glow));
    animation: pop .35s cubic-bezier(.34,1.56,.64,1);
  }
  @keyframes pop {
    0%   { transform: scale(1); }
    60%  { transform: scale(1.32); }
    100% { transform: scale(1.18); }
  }
  .seat.sold { cursor: not-allowed; opacity: 0.55; }
  .seat.sold:hover { transform: none; filter: none; }
  .seat.reserved { cursor: not-allowed; opacity: 0.7; }
  .seat.reserved:hover { transform: scale(1.05); filter: drop-shadow(0 2px 4px rgba(15,23,42,0.18)); }

  .seat .seat-body { fill: var(--available); transition: fill .12s; }
  .seat .seat-highlight { fill: rgba(255,255,255,0.18); pointer-events: none; }
  .seat.reserved .seat-body { fill: var(--reserved); }
  .seat.sold .seat-body { fill: var(--sold); }
  .seat.vip .seat-body { fill: var(--gold); }
  .seat.vip { filter: drop-shadow(0 0 4px rgba(228,192,94,0.45)); }
  .seat.selected .seat-body { fill: var(--selected); }
  .seat.selected { filter: drop-shadow(0 0 6px var(--selected-glow)); }
  .seat:hover .seat-body { stroke: rgba(15,23,42,0.35); stroke-width: 1; }

  /* Section labels and decorations */
  .section-label-bg {
    fill: rgba(255,255,255,0.96);
    stroke: var(--line-2);
    stroke-width: 1.2;
    filter: drop-shadow(0 4px 10px rgba(15,23,42,0.10));
  }
  .section-label-letter {
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 30px;
    fill: var(--primary);
    text-anchor: middle;
    dominant-baseline: middle;
    letter-spacing: 0.02em;
  }
  .section-label-count {
    font-family: 'Tajawal', sans-serif;
    font-weight: 600;
    font-size: 10.5px;
    fill: var(--ink-3);
    text-anchor: middle;
    dominant-baseline: middle;
    letter-spacing: 0.04em;
  }
  .section-label-sub {
    font-family: 'Tajawal', sans-serif;
    font-size: 13px;
    fill: var(--ink-3);
    text-anchor: middle;
  }
  .row-number {
    font-family: 'Tajawal', sans-serif;
    font-size: 12px;
    fill: var(--ink-3);
    font-weight: 700;
    text-anchor: middle;
    dominant-baseline: middle;
  }
  .row-number-bg {
    fill: rgba(255,255,255,0.85);
    stroke: var(--line);
    stroke-width: 1;
  }
  .aisle-line {
    stroke: var(--line-2);
    stroke-width: 1.5;
    stroke-dasharray: 4 6;
    fill: none;
    opacity: 0.55;
  }
  .balcony-divider-arc {
    stroke: var(--line-2);
    stroke-width: 2;
    stroke-dasharray: 8 8;
    fill: none;
    opacity: 0.7;
  }
  .balcony-divider-text {
    font-family: 'Tajawal', sans-serif;
    font-weight: 700;
    font-size: 13px;
    fill: var(--ink-3);
    text-anchor: middle;
    letter-spacing: 0.18em;
  }
  .balcony-divider {
    stroke: var(--line-2);
    stroke-width: 2;
    stroke-dasharray: 6 6;
    fill: none;
  }
  .stage-arc {
    fill: url(#stageGrad);
    stroke: var(--primary);
    stroke-width: 2;
  }
  .curtain-left, .curtain-right {
    fill: url(#curtainGrad);
  }
  .stage-text {
    font-family: 'Cairo', sans-serif;
    font-weight: 800;
    font-size: 26px;
    fill: #fff;
    text-anchor: middle;
    letter-spacing: 0.18em;
  }
  .stage-text-en {
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    font-size: 11px;
    fill: rgba(228,192,94,0.85);
    text-anchor: middle;
    letter-spacing: 0.6em;
  }

  /* ---------- Toast ---------- */
  .toast {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--ink);
    color: #fff;
    padding: 12px 18px;
    border-radius: 12px;
    font-family: 'Tajawal', sans-serif;
    font-size: 13px;
    box-shadow: var(--shadow-lg);
    opacity: 0;
    transition: all .25s ease;
    z-index: 200;
    pointer-events: none;
  }
  .toast.visible { opacity: 1; transform: translateX(-50%) translateY(0); }
  .toast.error { background: var(--sold-dark); }

  /* ---------- Loading / curtain reveal ---------- */
  .stage-reveal { animation: curtainOpen 1.6s cubic-bezier(.65,.05,.36,1) both; }
  .curtain-left { animation: curtainSlideLeft 1.6s cubic-bezier(.65,.05,.36,1) both; transform-origin: right center; }
  .curtain-right { animation: curtainSlideRight 1.6s cubic-bezier(.65,.05,.36,1) both; transform-origin: left center; }
  @keyframes curtainSlideLeft { 0%,30% { transform: translateX(0); } 100% { transform: translateX(-15%); } }
  @keyframes curtainSlideRight { 0%,30% { transform: translateX(0); } 100% { transform: translateX(15%); } }
  @keyframes curtainOpen { from { opacity: 0; } to { opacity: 1; } }

  .seats-fade-in .seat {
    opacity: 1;
  }

  /* responsive */
  @media (max-width: 760px) {
    .app { grid-template-columns: 1fr; grid-template-rows: 64px 1fr auto; }
    aside.side-panel {
      max-height: 45vh;
      border-right: 0;
      border-top: 1px solid var(--line);
    }
    .legend { display: none; }
    .minimap { display: none; }
  }

  /* إخفاء Sidebar الإحصائيات */
  aside.sidebar { display: none !important; }


  /* Event Selector */
  .event-selector {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    font-family: 'Cairo', sans-serif;
  }
  .event-selector label {
    font-weight: 600;
    color: #0C4A6E;
    font-size: 14px;
  }
  .event-selector select {
    padding: 8px 14px;
    border: 1.5px solid #E2E8F0;
    border-radius: 8px;
    font-family: 'Cairo', sans-serif;
    font-size: 13px;
    min-width: 280px;
    background: #F8FAFC;
    cursor: pointer;
    transition: all 0.2s;
  }
  .event-selector select:focus {
    outline: none;
    border-color: #0369A1;
    box-shadow: 0 0 0 3px rgba(3,105,161,0.1);
  }
  .event-info-pill {
    padding: 6px 14px;
    background: #E0F2FE;
    color: #0C4A6E;
    border-radius: 999px;
    font-weight: 600;
    font-size: 12px;
  }

</style>
</head>
<body>

{{-- Event Selector --}}
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

{{-- Hidden data for JS --}}
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
          // أسماء الأيام والأشهر بالعربية
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

          // الوقت بنظام 12 ساعة عربي
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

<script>
/* =====================================================================
   SEATING DATA — University of Mosul Theater · 997 seats
   seat.io-style fan layout: rows arc concentrically around the stage.
   ===================================================================== */

const PRICES = {
  vip:       75000,
  balcony:   45000,
  orchestra: 25000,
};

function seededRandom(seed) {
  let s = seed;
  return () => { s = (s * 9301 + 49297) % 233280; return s / 233280; };
}
const rng = seededRandom(7341);

function rollStatus(prob) {
  const r = rng();
  if (r < prob.sold) return "sold";
  if (r < prob.sold + prob.reserved) return "reserved";
  return "available";
}

/* ─── Fan-arc geometry ─────────────────────────────────────────────
   Stage is at top. Rows curve around a virtual focal point well above
   the stage. Each section occupies an angular wedge of the fan.

   FAN_CENTER: focal point of all arcs (above stage).
   Sections defined by [angleStart, angleEnd] (degrees from vertical).
*/
const FAN = {
  cx: 850,           // canvas-x of fan center
  cy: -800,          // canvas-y (above the stage, negative)
  // Orchestra rows arc from radius 1320 → ~1820 (front row pushed below stage)
  orchRadiusStart: 1320,
  orchRowGap:      36,
  orchRows:        15,
  // Balcony arcs from 2000 → 2266
  balcRadiusStart: 2000,
  balcRowGap:      38,
  balcRows:        8,
};

// Total seats per row in each section (sums to 997)
const SECTIONS = {
  // ── Orchestra (Section A right · B center · C left) ──
  // Aisles: 4° gap between sections (B↔A, B↔C)
  // angle in degrees, 0° = straight down from FAN center, +° = right
  C: { angles: [-34, -16], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
  B: { angles: [-12,  12], rowSeats: [13,14,14,15,15,16,16,17,17,18,18,19,19,20,20], floor: "orchestra" },
  A: { angles: [ 16,  34], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
  // ── Balcony — same aisle treatment ──
  F: { angles: [-32, -16], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
  E: { angles: [-12,  12], rowSeats: [16,16,17,17,18,18,19,19], floor: "balcony" },
  D: { angles: [ 16,  32], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
};

const seats = [];
const sectionMeta = [];
let totalCount = 0;

function buildSection(name, cfg) {
  const isBalc = cfg.floor === "balcony";
  const radiusStart = isBalc ? FAN.balcRadiusStart : FAN.orchRadiusStart;
  const rowGap      = isBalc ? FAN.balcRowGap : FAN.orchRowGap;
  const [aStart, aEnd] = cfg.angles;

  cfg.rowSeats.forEach((seatCount, rIdx) => {
    const r = rIdx + 1;
    const radius = radiusStart + rIdx * rowGap;
    const isVipRow = !isBalc && r === 10;

    // Seat positions: evenly spaced angularly within section wedge
    for (let i = 0; i < seatCount; i++) {
      const t = seatCount === 1 ? 0.5 : i / (seatCount - 1);
      const angDeg = aStart + t * (aEnd - aStart);
      const angRad = (angDeg * Math.PI) / 180;
      const x = FAN.cx + Math.sin(angRad) * radius;
      const y = FAN.cy + Math.cos(angRad) * radius;

      const isVip = isVipRow || isBalc;
      const prob = isVipRow ? { sold: 0.18, reserved: 0.10 }
                : isBalc    ? { sold: 0.22, reserved: 0.12 }
                            : { sold: 0.28, reserved: 0.14 };
      const status = rollStatus(prob);
      const price = isVipRow ? PRICES.vip
                  : isBalc    ? PRICES.balcony
                              : PRICES.orchestra;

      seats.push({
        id: `${name}-${r}-${i+1}`,
        section: name, row: r, seat: i+1,
        floor: cfg.floor,
        status,
        type: isVip ? "vip" : "standard",
        price,
        angle: angDeg,
        x: Math.round(x*10)/10,
        y: Math.round(y*10)/10,
      });
      totalCount++;
    }
  });

  // Section label position: centered, above outermost row
  const aMid = (cfg.angles[0] + cfg.angles[1]) / 2;
  const aRad = (aMid * Math.PI) / 180;
  const labelRadius = radiusStart - 60;
  sectionMeta.push({
    id: name,
    name: `القسم ${name}`,
    cx: Math.round(FAN.cx + Math.sin(aRad) * labelRadius),
    cy: Math.round(FAN.cy + Math.cos(aRad) * labelRadius),
    floor: cfg.floor,
  });
}

["C","B","A"].forEach(n => buildSection(n, SECTIONS[n]));
["F","E","D"].forEach(n => buildSection(n, SECTIONS[n]));

window.SEATING_DATA = {
  seats,
  total: totalCount,
  sectionMeta,
  prices: PRICES,
  fan: FAN,
};

console.log(`✓ Generated ${totalCount} seats`);

// ─── Fetch reservations from Laravel API ───
if (window.SELECTED_EVENT_ID) {
  fetch(`/api/seats/${window.SELECTED_EVENT_ID}`)
    .then(r => r.json())
    .then(data => {
      console.log('✓ Reservations loaded:', data.count);
      const reservations = data.reservations || {};
      
      // تحديث حالة المقاعد
      seats.forEach(seat => {
        const reservation = reservations[seat.id];
        if (reservation) {
          seat.status = reservation.status === 'checked_in' ? 'sold' : 'reserved';
          seat.guest_name = reservation.guest_name;
        } else {
          seat.status = 'available';
        }
      });
      
      // إعادة رسم الخارطة لو ممكن
      if (typeof window.refreshSeats === 'function') {
        window.refreshSeats();
      }
    })
    .catch(err => console.error('Failed to load reservations:', err));
}


</script>
<script>
/* =====================================================================
   SEATING APP — fan-arc layout, pan/zoom, selection, checkout
   ===================================================================== */
(function() {
  const { seats, total, sectionMeta, fan } = window.SEATING_DATA;
  const SVG_NS = "http://www.w3.org/2000/svg";
  const mapSvg     = document.getElementById("mapSvg");
  const seatsGroup = document.getElementById("seatsGroup");
  const labelsGroup= document.getElementById("labelsGroup");
  const tooltip    = document.getElementById("tooltip");
  const mapCanvas  = document.getElementById("mapCanvas");

  const seatById = new Map();
  function toAR(n) { return String(n).replace(/[0-9]/g, d => "٠١٢٣٤٥٦٧٨٩"[d]); }

  // ─── Section labels — badge style: letter + seat count ─────────
  // Labels positioned BEHIND the rear-most row, in the section's angular center.
  const sectionCounts = {};
  seats.forEach(s => sectionCounts[s.section] = (sectionCounts[s.section] || 0) + 1);

  sectionMeta.forEach(meta => {
    const cnt = sectionCounts[meta.id] || 0;
    const isPremium = meta.id === "B" || meta.id === "E"; // center sections
    // Badge: rounded rect with section letter and count
    const w = 68, h = 52;
    const bg = document.createElementNS(SVG_NS, "rect");
    bg.setAttribute("x", meta.cx - w/2);
    bg.setAttribute("y", meta.cy - h/2);
    bg.setAttribute("width", w);
    bg.setAttribute("height", h);
    bg.setAttribute("rx", 9);
    bg.setAttribute("class", "section-label-bg");
    if (isPremium) {
      bg.setAttribute("stroke", "#E4C05E");
      bg.setAttribute("stroke-width", "1.8");
    }
    labelsGroup.appendChild(bg);

    const letter = document.createElementNS(SVG_NS, "text");
    letter.setAttribute("x", meta.cx);
    letter.setAttribute("y", meta.cy - 5);
    letter.setAttribute("class", "section-label-letter");
    if (isPremium) letter.setAttribute("fill", "#C9A445");
    letter.textContent = meta.id;
    labelsGroup.appendChild(letter);

    const sub = document.createElementNS(SVG_NS, "text");
    sub.setAttribute("x", meta.cx);
    sub.setAttribute("y", meta.cy + 14);
    sub.setAttribute("class", "section-label-count");
    sub.textContent = `${toAR(cnt)} مقعد`;
    labelsGroup.appendChild(sub);
  });

  // ─── Aisle lines: dashed radial lines between sections ─────────
  // Aisle angles for orchestra: -14, +14 ; balcony: -14, +14
  const aisleSpec = [
    { angle: -14, floor: "orchestra" },
    { angle:  14, floor: "orchestra" },
    { angle: -14, floor: "balcony" },
    { angle:  14, floor: "balcony" },
  ];
  aisleSpec.forEach(({ angle, floor }) => {
    const isBalc = floor === "balcony";
    const r1 = (isBalc ? fan.balcRadiusStart : fan.orchRadiusStart) - 50;
    const r2 = (isBalc ? fan.balcRadiusStart + 7 * fan.balcRowGap
                       : fan.orchRadiusStart + 14 * fan.orchRowGap) + 30;
    const a = angle * Math.PI / 180;
    const x1 = fan.cx + Math.sin(a) * r1, y1 = fan.cy + Math.cos(a) * r1;
    const x2 = fan.cx + Math.sin(a) * r2, y2 = fan.cy + Math.cos(a) * r2;
    const line = document.createElementNS(SVG_NS, "line");
    line.setAttribute("x1", x1); line.setAttribute("y1", y1);
    line.setAttribute("x2", x2); line.setAttribute("y2", y2);
    line.setAttribute("class", "aisle-line");
    labelsGroup.appendChild(line);
  });

  // ─── Balcony divider: arc between orchestra and balcony ─────────
  const dividerR = (fan.orchRadiusStart + 14 * fan.orchRowGap + fan.balcRadiusStart) / 2;
  const aL = -38 * Math.PI / 180, aR = 38 * Math.PI / 180;
  const dx1 = fan.cx + Math.sin(aL) * dividerR, dy1 = fan.cy + Math.cos(aL) * dividerR;
  const dx2 = fan.cx + Math.sin(aR) * dividerR, dy2 = fan.cy + Math.cos(aR) * dividerR;
  const divArc = document.createElementNS(SVG_NS, "path");
  divArc.setAttribute("d", `M ${dx1} ${dy1} A ${dividerR} ${dividerR} 0 0 1 ${dx2} ${dy2}`);
  divArc.setAttribute("class", "balcony-divider-arc");
  labelsGroup.appendChild(divArc);
  // Balcony label sits on top of the arc, centered
  const dividerMidR = dividerR;
  const dividerLabelX = fan.cx;
  const dividerLabelY = fan.cy + dividerMidR + 4;
  const divLabelBg = document.createElementNS(SVG_NS, "rect");
  divLabelBg.setAttribute("x", dividerLabelX - 90);
  divLabelBg.setAttribute("y", dividerLabelY - 16);
  divLabelBg.setAttribute("width", 180);
  divLabelBg.setAttribute("height", 28);
  divLabelBg.setAttribute("rx", 14);
  divLabelBg.setAttribute("fill", "rgba(248,250,252,0.96)");
  divLabelBg.setAttribute("stroke", "#CBD5E1");
  divLabelBg.setAttribute("stroke-width", "1");
  labelsGroup.appendChild(divLabelBg);
  const divText = document.createElementNS(SVG_NS, "text");
  divText.setAttribute("x", dividerLabelX);
  divText.setAttribute("y", dividerLabelY + 4);
  divText.setAttribute("class", "balcony-divider-text");
  divText.textContent = "الشُّرفة · BALCONY";
  labelsGroup.appendChild(divText);

  // ─── Row numbers (placed at right edge of each row, outside section) ─
  const rowsBySection = {};
  seats.forEach(s => {
    const k = `${s.section}-${s.row}`;
    if (!rowsBySection[k]) rowsBySection[k] = { section: s.section, row: s.row, floor: s.floor, seats: [] };
    rowsBySection[k].seats.push(s);
  });
  Object.values(rowsBySection).forEach(row => {
    // Use the seat with largest absolute angle as the outer-edge anchor
    row.seats.sort((a, b) => Math.abs(b.angle) - Math.abs(a.angle));
    const edge = row.seats[0];
    // Push outward along the radial vector by ~28
    const a = edge.angle * Math.PI / 180;
    const radial = { x: Math.sin(a), y: Math.cos(a) };
    const r = Math.hypot(edge.x - fan.cx, edge.y - fan.cy);
    const newR = r + 22;
    const rx = fan.cx + radial.x * newR, ry = fan.cy + radial.y * newR;
    const bg = document.createElementNS(SVG_NS, "circle");
    bg.setAttribute("cx", rx); bg.setAttribute("cy", ry);
    bg.setAttribute("r", 11);
    bg.setAttribute("class", "row-number-bg");
    labelsGroup.appendChild(bg);
    const t = document.createElementNS(SVG_NS, "text");
    t.setAttribute("x", rx); t.setAttribute("y", ry);
    t.setAttribute("class", "row-number");
    t.textContent = toAR(row.row);
    labelsGroup.appendChild(t);
  });

  // ─── Build seats ────────────────────────────────────────────────
  // Each seat is rotated to face the fan center (i.e. the stage).
  let i = 0;
  seats.forEach(s => {
    // Rotate seat so its "back" points away from the stage.
    const wrap = document.createElementNS(SVG_NS, "g");
    wrap.setAttribute("transform", `translate(${s.x},${s.y}) rotate(${s.angle})`);

    const g = document.createElementNS(SVG_NS, "g");
    g.setAttribute("class", `seat ${s.status} ${s.type}`);
    g.setAttribute("data-id", s.id);
    // Stagger entry: seats further from stage come in slightly later, with section variation
    const baseDelay = 1.1; // after stage curtain settles
    const rowDelay = (s.row - 1) * 0.025;
    const sectionOffset = s.floor === "balcony" ? 0.4 : 0;
    g.style.animationDelay = `${baseDelay + rowDelay + sectionOffset}s`;

    // Single rounded square - clean, seat.io style
    const body = document.createElementNS(SVG_NS, "rect");
    body.setAttribute("x", "-9");
    body.setAttribute("y", "-9");
    body.setAttribute("width", "18");
    body.setAttribute("height", "18");
    body.setAttribute("rx", "4");
    body.setAttribute("class", "seat-body");
    g.appendChild(body);

    // Subtle top highlight (1px stripe at the seat-back top)
    const hl = document.createElementNS(SVG_NS, "rect");
    hl.setAttribute("x", "-9");
    hl.setAttribute("y", "-9");
    hl.setAttribute("width", "18");
    hl.setAttribute("height", "3");
    hl.setAttribute("rx", "4");
    hl.setAttribute("class", "seat-highlight");
    g.appendChild(hl);

    // Seat number (revealed at high zoom) — counter-rotated
    const numWrap = document.createElementNS(SVG_NS, "g");
    numWrap.setAttribute("transform", `rotate(${-s.angle})`);
    const num = document.createElementNS(SVG_NS, "text");
    num.setAttribute("x", "0");
    num.setAttribute("y", "0");
    num.setAttribute("class", "seat-num-label");
    num.setAttribute("text-anchor", "middle");
    num.setAttribute("dominant-baseline", "central");
    num.setAttribute("font-size", "8");
    num.setAttribute("fill", "#fff");
    num.setAttribute("font-weight", "700");
    num.style.opacity = "0";
    num.style.pointerEvents = "none";
    num.textContent = toAR(s.seat);
    numWrap.appendChild(num);
    g.appendChild(numWrap);

    wrap.appendChild(g);
    seatsGroup.appendChild(wrap);
    seatById.set(s.id, { data: s, el: g, numEl: num });
  });

  function updateSeatLabelVisibility() {
    const show = scale > 2.4;
    seatsGroup.querySelectorAll(".seat-num-label").forEach(el => {
      el.style.opacity = show ? 0.92 : 0;
      el.style.transition = "opacity .2s";
    });
  }

  // ─── Pan / zoom ────────────────────────────────────────────────
  const VB = { x: -450, y: 50, w: 2600, h: 1500 };
  let scale = 1, tx = 0, ty = 0;
  const MIN_SCALE = 0.6, MAX_SCALE = 5;

  function applyTransform() {
    const w = VB.w / scale, h = VB.h / scale;
    let vx = VB.x + (VB.w - w) / 2 - tx;
    let vy = VB.y + (VB.h - h) / 2 - ty;
    vx = Math.max(VB.x - 300, Math.min(VB.x + VB.w - w + 300, vx));
    vy = Math.max(VB.y - 200, Math.min(VB.y + VB.h - h + 200, vy));
    mapSvg.setAttribute("viewBox", `${vx} ${vy} ${w} ${h}`);
    document.getElementById("zoomLevel").textContent = Math.round(scale * 100) + "%";
    updateMinimap(vx, vy, w, h);
    updateSeatLabelVisibility();
  }

  function setZoom(newScale, cx, cy) {
    newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));
    if (cx !== undefined && cy !== undefined) {
      const rect = mapSvg.getBoundingClientRect();
      const vb = mapSvg.viewBox.baseVal;
      const svgX = vb.x + (cx - rect.left) / rect.width * vb.width;
      const svgY = vb.y + (cy - rect.top) / rect.height * vb.height;
      const newW = VB.w / newScale, newH = VB.h / newScale;
      const desiredVx = svgX - (cx - rect.left) / rect.width * newW;
      const desiredVy = svgY - (cy - rect.top) / rect.height * newH;
      tx = VB.x + (VB.w - newW) / 2 - desiredVx;
      ty = VB.y + (VB.h - newH) / 2 - desiredVy;
    }
    scale = newScale;
    applyTransform();
  }

  document.getElementById("zoomIn").onclick = () => setZoom(scale * 1.3);
  document.getElementById("zoomOut").onclick = () => setZoom(scale / 1.3);
  document.getElementById("zoomReset").onclick = () => { scale = 1; tx = 0; ty = 0; applyTransform(); };
  document.getElementById("resetBtn").onclick = () => { scale = 1; tx = 0; ty = 0; applyTransform(); };

  mapCanvas.addEventListener("wheel", e => {
    e.preventDefault();
    const factor = e.deltaY < 0 ? 1.12 : 1/1.12;
    setZoom(scale * factor, e.clientX, e.clientY);
  }, { passive: false });

  // Drag pan
  let isDragging = false, didDrag = false, dragStart = null, panStart = null;
  mapCanvas.addEventListener("pointerdown", e => {
    if (e.target.closest(".floor-toggle, .legend, .zoom-controls, .minimap")) return;
    isDragging = true; didDrag = false;
    dragStart = { x: e.clientX, y: e.clientY };
    panStart = { tx, ty };
    mapCanvas.classList.add("dragging");
    mapCanvas.setPointerCapture(e.pointerId);
  });
  mapCanvas.addEventListener("pointermove", e => {
    if (!isDragging) return;
    const dx = e.clientX - dragStart.x;
    const dy = e.clientY - dragStart.y;
    if (Math.abs(dx) + Math.abs(dy) > 4) didDrag = true;
    const rect = mapSvg.getBoundingClientRect();
    const vb = mapSvg.viewBox.baseVal;
    tx = panStart.tx + dx * (vb.width / rect.width);
    ty = panStart.ty + dy * (vb.height / rect.height);
    applyTransform();
  });
  mapCanvas.addEventListener("pointerup", () => {
    isDragging = false;
    mapCanvas.classList.remove("dragging");
  });

  // Pinch zoom
  const pointers = new Map();
  let pinchStartDist = 0, pinchStartScale = 1;
  mapCanvas.addEventListener("pointerdown", e => pointers.set(e.pointerId, e));
  mapCanvas.addEventListener("pointermove", e => {
    if (pointers.has(e.pointerId)) pointers.set(e.pointerId, e);
    if (pointers.size === 2) {
      const [a, b] = [...pointers.values()];
      const d = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
      if (!pinchStartDist) { pinchStartDist = d; pinchStartScale = scale; }
      const cx = (a.clientX + b.clientX) / 2, cy = (a.clientY + b.clientY) / 2;
      setZoom(pinchStartScale * (d / pinchStartDist), cx, cy);
    }
  });
  mapCanvas.addEventListener("pointerup", e => { pointers.delete(e.pointerId); pinchStartDist = 0; });
  mapCanvas.addEventListener("pointercancel", e => { pointers.delete(e.pointerId); pinchStartDist = 0; });

  // ─── Tooltip ────────────────────────────────────────────────────
  function showTooltip(seat, ev) {
    const statusLabel = { available: "متاح", reserved: "محجوز", sold: "مشغول" }[seat.status];
    const statusColor = { available: "#22C55E", reserved: "#94A3B8", sold: "#EF4444" }[seat.status];
    const isVip = seat.type === "vip";
    tooltip.innerHTML = `
      <div class="tt-title">${isVip ? "★ " : ""}مقعد ${seat.section}-${toAR(seat.row)}-${toAR(seat.seat)}</div>
      <div class="tt-row"><span class="dot" style="background:${statusColor}"></span> ${statusLabel}${isVip ? " · VIP" : ""}</div>
      <div class="tt-row" style="opacity:.7">صف ${toAR(seat.row)} · القسم ${seat.section}</div>
    `;
    const rect = mapCanvas.getBoundingClientRect();
    tooltip.style.left = (ev.clientX - rect.left) + "px";
    tooltip.style.top = (ev.clientY - rect.top) + "px";
    tooltip.classList.add("visible");
  }
  function hideTooltip() { tooltip.classList.remove("visible"); }

  seatsGroup.addEventListener("mouseover", e => {
    const g = e.target.closest(".seat");
    if (!g) return;
    const entry = seatById.get(g.getAttribute("data-id"));
    if (entry) showTooltip(entry.data, e);
  });
  seatsGroup.addEventListener("mousemove", e => {
    const g = e.target.closest(".seat");
    if (g) {
      const rect = mapCanvas.getBoundingClientRect();
      tooltip.style.left = (e.clientX - rect.left) + "px";
      tooltip.style.top = (e.clientY - rect.top) + "px";
    }
  });
  seatsGroup.addEventListener("mouseout", e => {
    if (!e.relatedTarget || !e.relatedTarget.closest || !e.relatedTarget.closest(".seat")) hideTooltip();
  });

  // ─── Selection ─────────────────────────────────────────────────
  const selected = new Set();

  seatsGroup.addEventListener("click", e => {
    if (didDrag) return;
    const g = e.target.closest(".seat");
    if (!g) return;
    const entry = seatById.get(g.getAttribute("data-id"));
    if (!entry) return;
    const s = entry.data;
    if (s.status === "sold" || s.status === "reserved") {
      toast(s.status === "sold" ? "هذا المقعد مشغول" : "هذا المقعد محجوز", true);
      return;
    }
    toggleSelect(s.id);
  });

  function toggleSelect(id) {
    const entry = seatById.get(id);
    if (!entry) return;
    if (selected.has(id)) {
      selected.delete(id);
      entry.el.classList.remove("selected");
    } else {
      if (selected.size >= 12) { toast("الحد الأقصى ١٢ مقعد في الحجز الواحد", true); return; }
      selected.add(id);
      entry.el.classList.add("selected");
    }
    renderSelected();
  }

  function renderSelected() {
    const list = document.getElementById("selectedList");
    const empty = document.getElementById("emptyState");
    const count = document.getElementById("selectedCount");
    const totalEl = document.getElementById("totalPrice");
    const btn = document.getElementById("checkoutBtn");
    count.textContent = `${toAR(selected.size)} ${selected.size === 1 ? "مقعد" : "مقاعد"}`;
    if (selected.size === 0) {
      list.innerHTML = "";
      list.appendChild(empty);
      totalEl.textContent = "٠";
      btn.disabled = true;
      return;
    }
    btn.disabled = false;
    list.innerHTML = "";
    [...selected].forEach(id => {
      const s = seatById.get(id).data;
      const row = document.createElement("div");
      row.className = "selected-row";
      row.innerHTML = `
        <div>
          <div class="seat-id">${s.type === "vip" ? "★ " : ""}${s.section}-${toAR(s.row)}-${toAR(s.seat)}</div>
          <div class="seat-meta">${s.floor === "orchestra" ? "أرضي" : "شرفة"} · صف ${toAR(s.row)}${s.type === "vip" ? " · VIP" : ""}</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px">
          <button class="remove-btn" data-id="${id}" title="إزالة">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>`;
      list.appendChild(row);
    });
    totalEl.textContent = toAR(selected.size);
  }

  document.getElementById("selectedList").addEventListener("click", e => {
    const btn = e.target.closest(".remove-btn");
    if (!btn) return;
    toggleSelect(btn.getAttribute("data-id"));
  });

  document.getElementById("checkoutBtn").onclick = () => {
    toast(`تم تأكيد ${toAR(selected.size)} مقعد · سيتم تحويلك إلى الدفع...`);
  };

  // ─── Best available ─────────────────────────────────────────────
  let baCount = 2;
  function updateBA() {
    document.getElementById("baCount").innerHTML = `<small>عدد المقاعد:</small>${toAR(baCount)}`;
  }
  document.getElementById("baMinus").onclick = () => { baCount = Math.max(1, baCount - 1); updateBA(); };
  document.getElementById("baPlus").onclick = () => { baCount = Math.min(8, baCount + 1); updateBA(); };
  updateBA();

  document.getElementById("bestAvailableBtn").onclick = () => {
    const candidates = seats.filter(s => s.status === "available" && !selected.has(s.id));
    const byRow = {};
    candidates.forEach(s => {
      const k = `${s.section}-${s.row}`;
      (byRow[k] = byRow[k] || []).push(s);
    });
    let best = null, bestScore = -Infinity;
    Object.entries(byRow).forEach(([k, arr]) => {
      arr.sort((a, b) => a.seat - b.seat);
      for (let i = 0; i + baCount <= arr.length; i++) {
        const block = arr.slice(i, i + baCount);
        let contiguous = true;
        for (let j = 1; j < block.length; j++) {
          if (block[j].seat !== block[j-1].seat + 1) { contiguous = false; break; }
        }
        if (!contiguous) continue;
        const sec = block[0].section, row = block[0].row;
        const sectionScore = sec === "B" ? 100 : (sec === "A" || sec === "C") ? 70 : 40;
        const rowIdeal = block[0].floor === "orchestra" ? 7 : 4;
        const rowScore = 100 - Math.abs(row - rowIdeal) * 8;
        const vipBonus = block.some(s => s.type === "vip") ? 30 : 0;
        const score = sectionScore + rowScore + vipBonus;
        if (score > bestScore) { bestScore = score; best = block; }
      }
    });
    if (!best) { toast("لا توجد مقاعد متجاورة كافية متاحة", true); return; }
    best.forEach(s => {
      if (!selected.has(s.id) && selected.size < 12) {
        selected.add(s.id);
        seatById.get(s.id).el.classList.add("selected");
      }
    });
    renderSelected();
    const cx = best.reduce((a, s) => a + s.x, 0) / best.length;
    const cy = best.reduce((a, s) => a + s.y, 0) / best.length;
    scale = 2.2;
    tx = (VB.x + VB.w/2) - cx;
    ty = (VB.y + VB.h/2) - cy;
    applyTransform();
    toast(`تم اختيار ${toAR(best.length)} مقعد متجاور في القسم ${best[0].section} - صف ${toAR(best[0].row)}`);
  };

  // ─── Floor toggle ───────────────────────────────────────────────
  const floorButtons = document.querySelectorAll(".floor-toggle button");
  floorButtons.forEach(btn => {
    btn.onclick = () => {
      floorButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      const floor = btn.dataset.floor;
      seatsGroup.querySelectorAll(".seat").forEach(g => {
        const s = seatById.get(g.getAttribute("data-id")).data;
        g.style.display = (floor === "all" || s.floor === floor) ? "" : "none";
      });
      // Computed scales fit each floor's full width inside the viewBox
      if (floor === "orchestra") { scale = 1.18; tx = 0; ty = 141; }
      else if (floor === "balcony") { scale = 1.0; tx = 0; ty = -381; }
      else { scale = 1; tx = 0; ty = 0; }
      applyTransform();
    };
  });

  document.getElementById("fullscreenBtn").onclick = () => {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
    else document.exitFullscreen?.();
  };

  // ─── Toast ──────────────────────────────────────────────────────
  let toastTimeout;
  function toast(msg, isErr) {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.className = "toast visible" + (isErr ? " error" : "");
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => t.classList.remove("visible"), 2400);
  }

  // ─── Minimap ────────────────────────────────────────────────────
  const minimapSvg = document.querySelector("#minimap svg");
  minimapSvg.innerHTML = "";
  // Stage hint on minimap
  const stageHint = document.createElementNS(SVG_NS, "path");
  stageHint.setAttribute("d", "M 380 230 Q 850 100 1320 230 L 1320 270 Q 850 140 380 270 Z");
  stageHint.setAttribute("fill", "#0C4A6E");
  stageHint.setAttribute("opacity", "0.5");
  minimapSvg.appendChild(stageHint);
  // Tiny dots
  seats.forEach(s => {
    const c = document.createElementNS(SVG_NS, "circle");
    c.setAttribute("cx", s.x); c.setAttribute("cy", s.y); c.setAttribute("r", "5");
    let fill = "#22C55E";
    if (s.status === "sold") fill = "#EF4444";
    else if (s.status === "reserved") fill = "#94A3B8";
    else if (s.type === "vip") fill = "#E4C05E";
    c.setAttribute("fill", fill);
    c.setAttribute("opacity", "0.85");
    minimapSvg.appendChild(c);
  });
  const vp = document.createElementNS(SVG_NS, "rect");
  vp.setAttribute("class", "minimap-viewport");
  vp.setAttribute("fill", "rgba(3,105,161,0.15)");
  vp.setAttribute("stroke", "#0369A1");
  vp.setAttribute("stroke-width", "10");
  minimapSvg.appendChild(vp);
  minimapSvg.setAttribute("viewBox", "-450 50 2600 1500");

  function updateMinimap(vx, vy, vw, vh) {
    vp.setAttribute("x", vx); vp.setAttribute("y", vy);
    vp.setAttribute("width", vw); vp.setAttribute("height", vh);
  }

  minimapSvg.addEventListener("click", e => {
    const rect = minimapSvg.getBoundingClientRect();
    const px = VB.x + (e.clientX - rect.left) / rect.width * VB.w;
    const py = VB.y + (e.clientY - rect.top) / rect.height * VB.h;
    tx = (VB.x + VB.w/2) - px;
    ty = (VB.y + VB.h/2) - py;
    applyTransform();
  });

  applyTransform();
  setTimeout(() => toast(`تم تحميل ${toAR(total)} مقعد · انقر على المقاعد الخضراء لاختيار مقاعدك`), 1100);

  // ─── Minimap collapse toggle ────────────────────────────────────
  const minimap = document.getElementById("minimap");
  const minimapToggle = document.getElementById("minimapToggle");
  // Start collapsed so it doesn't obstruct the view
  minimap.classList.add("collapsed");
  function toggleMinimap() {
    minimap.classList.toggle("collapsed");
  }
  minimapToggle.addEventListener("click", e => {
    e.stopPropagation();
    toggleMinimap();
  });
  // Click anywhere on collapsed minimap to expand
  minimap.addEventListener("click", e => {
    if (minimap.classList.contains("collapsed") && e.target !== minimapToggle && !minimapToggle.contains(e.target)) {
      toggleMinimap();
    }
  });
})();

</script>

</body>
</html>
