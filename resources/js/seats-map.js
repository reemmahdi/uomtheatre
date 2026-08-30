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

      const isVip = false;
      const status = "available";
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

function loadReservations() {
  if (!window.SELECTED_EVENT_ID) return;
  fetch(`/api/seats/${window.SELECTED_EVENT_ID}`)
    .then(r => r.json())
    .then(data => {
      const reservations = data.reservations || {};
      const vipIds = new Set(data.vip_seat_ids || []);
      const blockedIds = new Set(data.blocked_seat_ids || []);

      seats.forEach(seat => {
        seat.type = (vipIds.has(seat.id) || blockedIds.has(seat.id))
          ? 'vip'
          : 'standard';

        const reservation = reservations[seat.id];
        if (reservation) {
          seat.status = reservation.status === 'checked_in' ? 'sold' : 'reserved';
          seat.guest_name = reservation.guest_name;
        } else {
          seat.status = 'available';
        }
      });

      if (typeof window.refreshSeats === 'function') {
        window.refreshSeats();
      }
    })
    .catch(() => {});
}

loadReservations();
setInterval(loadReservations, 8000);



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

  window.refreshSeats = function () {
    seatById.forEach(({ data, el }) => {
      const wasSelected = el.classList.contains("selected");
      const next = `seat ${data.status} ${data.type}` + (wasSelected ? " selected" : "");
      if (el.getAttribute("class") !== next) {
        el.setAttribute("class", next);
      }
    });
  };

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
