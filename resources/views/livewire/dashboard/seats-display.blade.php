@php
    $eventId = $selectedEvent?->id;
@endphp

<div>
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
    grid-template-columns: 1fr 380px;
    grid-template-rows: 64px 1fr;
    height: 100vh;
    background: var(--bg);
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

        /* Custom additions for Livewire integration */
        .event-selector {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .event-selector label {
            font-weight: 600;
            color: #0C4A6E;
            font-family: 'Cairo', sans-serif;
        }
        .event-selector select {
            padding: 10px 16px;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            min-width: 280px;
            background: #F8FAFC;
            cursor: pointer;
            transition: all 0.2s;
        }
        .event-selector select:hover {
            border-color: #0369A1;
        }
        .event-selector select:focus {
            outline: none;
            border-color: #0369A1;
            box-shadow: 0 0 0 3px rgba(3,105,161,0.1);
        }
        .no-event-msg {
            padding: 40px;
            text-align: center;
            color: #64748B;
            font-family: 'Cairo', sans-serif;
        }
    </style>

    {{-- Hidden data attribute for JS --}}
    @if($eventId)
        <div data-event-id="{{ $eventId }}" style="display:none;"></div>
    @endif

    {{-- Event Selector --}}
    <div class="event-selector">
        <label for="eventSelect">
            <i class="bi bi-calendar-event"></i>
            اختر الفعالية:
        </label>
        <select id="eventSelect" onchange="window.location.href = this.value ? `?event_id=${this.value}` : window.location.pathname">
            <option value="">-- اختر فعالية --</option>
            @foreach($events as $evt)
                <option value="{{ $evt->id }}" {{ $eventId == $evt->id ? 'selected' : '' }}>
                    {{ $evt->title }}
                    @if($evt->start_datetime)
                        ({{ $evt->start_datetime->format('Y-m-d') }})
                    @endif
                </option>
            @endforeach
        </select>

        @if($selectedEvent)
            <span class="pill" style="padding: 8px 16px; background: #E0F2FE; color: #0C4A6E; border-radius: 999px; font-family: 'Cairo'; font-weight: 600;">
                <i class="bi bi-check-circle-fill"></i>
                {{ $selectedEvent->title }}
            </span>
        @endif
    </div>

    @if(!$eventId)
        <div class="no-event-msg">
            <i class="bi bi-calendar-x" style="font-size: 64px; color: #94A3B8; margin-bottom: 16px;"></i>
            <h3 style="color: #0C4A6E; margin-bottom: 8px;">اختر فعالية لعرض حالة المقاعد</h3>
            <p>اختر الفعالية من القائمة أعلاه لرؤية المقاعد المحجوزة والمتاحة.</p>
        </div>
    @else


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
              1=>'كانون الثاني',2=>'شباط',3=>'آذار',4=>'نيسان',
              5=>'أيار',6=>'حزيران',7=>'تموز',8=>'آب',
              9=>'أيلول',10=>'تشرين الأول',11=>'تشرين الثاني',12=>'كانون الأول',
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





    @endif

    {{-- Load Scripts --}}
    @if($eventId)
        <script src="{{ asset('js/seating-data.js') }}"></script>
        <script src="{{ asset('js/seating-app.js') }}"></script>
    @endif
</div>
