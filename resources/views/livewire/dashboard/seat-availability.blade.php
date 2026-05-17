{{-- ════════════════════════════════════════════════════════════════
     شاشة تحديد المقاعد المتاحة — UOMTheatre (تصميم محدّث)

     ✨ التغييرات:
       - نفس FAN math من seats-map المعتمدة
       - viewBox="-450 50 2600 1500"
       - حذف "خشبة المسرح" المنفصلة (مدمجة داخل SVG)
       - Legend واضح بالألوان
       - keys بصيغة "A-01-01" لتتطابق مع DB
       - VIP محمي (غير قابل للاستبعاد)
     ════════════════════════════════════════════════════════════════ --}}

<div>

{{-- ✨ شريط العنوان والإجراءات --}}
<div class="card-custom p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1" style="color: #0C4A6E;">
                <i class="bi bi-grid-3x3-gap-fill" style="color: #0C4A6E;"></i>
                تحديد المقاعد المتاحة للجمهور
            </h5>
            <small class="text-muted">
                {{ $event->title }} — انقر على المقعد لاستبعاده أو إعادة إتاحته
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('dashboard.events') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right"></i> رجوع
            </a>
            <button id="btnResetAll" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
            </button>
            <button id="btnSave" class="btn btn-primary btn-sm">
                <i class="bi bi-save-fill"></i> <span id="saveText">حفظ التغييرات</span>
            </button>
        </div>
    </div>
</div>

{{-- ✨ الإحصائيات (3 بطاقات) --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom: 4px solid #22C55E;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="number" style="color: #22C55E;" id="availableCount">0</div>
                    <div class="label">
                        <i class="bi bi-check-circle-fill" style="color: #22C55E;"></i>
                        متاح للجمهور
                    </div>
                </div>
                <div class="icon" style="background: #dcfce7; color: #22C55E;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom: 4px solid #EF4444;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="number" style="color: #EF4444;" id="excludedCount">0</div>
                    <div class="label">
                        <i class="bi bi-x-circle-fill" style="color: #EF4444;"></i>
                        مستبعد (يظهر محجوزاً)
                    </div>
                </div>
                <div class="icon" style="background: #fef2f2; color: #EF4444;">
                    <i class="bi bi-slash-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-bottom: 4px solid #C9A530;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="number" style="color: #0C4A6E;">997</div>
                    <div class="label">
                        <i class="bi bi-bar-chart-fill" style="color: #0C4A6E;"></i>
                        إجمالي المقاعد
                    </div>
                </div>
                <div class="icon" style="background: #dbeafe; color: #0C4A6E;">
                    <i class="bi bi-grid"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ✨ أدوات سريعة + Legend --}}
<div class="card-custom p-3 mb-3">
    <div class="row g-3 align-items-center">

        {{-- أدوات الاستبعاد --}}
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <small class="fw-bold text-muted">أدوات سريعة:</small>
                <button id="btnIncludeAll" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-check2-square"></i> إتاحة الكل
                </button>
                <button id="btnExcludeAll" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-square"></i> استبعاد الكل
                </button>
                <small class="text-muted mx-1">|</small>
                <small class="fw-bold text-muted">استبعاد قسم:</small>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="A">A</button>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="B">B</button>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="C">C</button>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="D">D</button>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="E">E</button>
                <button class="btn btn-outline-secondary btn-sm section-toggle" data-section="F">F</button>
            </div>
        </div>

        {{-- Legend + Info Box --}}
        <div class="col-md-5">
            <div class="d-flex justify-content-end gap-3 flex-wrap mb-2" style="font-size: 13px;">
                <span>
                    <span style="display: inline-block; width: 14px; height: 14px; background: #22C55E; border-radius: 50%; vertical-align: middle; border: 1.5px solid #16A34A;"></span>
                    متاح
                </span>
                <span>
                    <span style="display: inline-block; width: 14px; height: 14px; background: #EF4444; border-radius: 50%; vertical-align: middle; border: 1.5px solid #DC2626;"></span>
                    مستبعد
                </span>
                <span>
                    <span style="display: inline-block; width: 14px; height: 14px; background: #C9A530; border-radius: 50%; vertical-align: middle; border: 1.5px solid #A88729;"></span>
                    VIP
                </span>
            </div>
            {{-- ✨ Info box للمقعد المحدد --}}
            <div id="seatInfoBox" class="text-end" style="font-size: 13px; padding: 8px 12px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; min-height: 38px; display: flex; align-items: center; justify-content: flex-end; color: #64748b;">
                اختر مقعداً لعرض تفاصيله
            </div>
        </div>
    </div>
</div>

{{-- ✨ خريطة المسرح --}}
<div class="card-custom p-3" style="background: linear-gradient(180deg, #F8FAFC, #EEF2F7);">
    <div id="mapWrapper" style="position: relative; border-radius: 12px; min-height: 600px; overflow: auto;">

        {{-- نفس الـ viewBox من الخريطة المعتمدة --}}
        <svg id="mapSvg" width="100%" viewBox="-450 50 2600 1700" preserveAspectRatio="xMidYMid meet" style="max-height: 750px; user-select: none;">

            {{-- gradients --}}
            <defs>
                <radialGradient id="stageGlow" cx="50%" cy="0%" r="80%">
                    <stop offset="0%" stop-color="#FCD981" stop-opacity="0.4"/>
                    <stop offset="100%" stop-color="#FCD981" stop-opacity="0"/>
                </radialGradient>
            </defs>

            {{-- ضوء المسرح --}}
            <ellipse cx="850" cy="320" rx="900" ry="280" fill="url(#stageGlow)" pointer-events="none"/>

            {{-- شريط المسرح داخل SVG (برغندي رسمي) --}}
            <rect x="200" y="280" width="1300" height="50" rx="8" fill="#7B1E2F" pointer-events="none"/>
            <text x="850" y="313" text-anchor="middle" fill="#fff" font-weight="700" font-size="28" pointer-events="none">
                خشبة المسرح
            </text>

            {{-- مجموعة المقاعد --}}
            <g id="seatsGroup"></g>

            {{-- مجموعة labels الأقسام --}}
            <g id="labelsGroup"></g>
        </svg>

        {{-- مؤشر تحميل --}}
        <div id="loadingOverlay" style="position: absolute; inset: 0; background: rgba(255,255,255,0.92); display: flex; align-items: center; justify-content: center; border-radius: 12px;">
            <div style="text-align: center;">
                <div class="spinner-border" style="color: #0C4A6E; width: 50px; height: 50px;"></div>
                <p class="mt-3 mb-0" style="color: #0C4A6E; font-weight: 600;">جارٍ تحميل الخريطة...</p>
            </div>
        </div>
    </div>
</div>

{{-- ✨ JavaScript --}}
@push('scripts')
<script>
(function() {
    'use strict';

    const EVENT_UUID = @json($event->uuid);
    const API_GET = `/api/events/${EVENT_UUID}/availability`;
    const API_SAVE = `/api/events/${EVENT_UUID}/availability/save`;

    const SVG_NS = "http://www.w3.org/2000/svg";

    // 🎯 نفس FAN math من الخريطة المعتمدة
    const FAN = {
        cx: 850,
        cy: -800,
        orchRadiusStart: 1320,
        orchRowGap: 36,
        balcRadiusStart: 2000,
        balcRowGap: 38,
    };

    const SECTIONS = {
        C: { angles: [-34, -16], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
        B: { angles: [-12,  12], rowSeats: [13,14,14,15,15,16,16,17,17,18,18,19,19,20,20], floor: "orchestra" },
        A: { angles: [ 16,  34], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
        F: { angles: [-32, -16], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
        E: { angles: [-12,  12], rowSeats: [16,16,17,17,18,18,19,19], floor: "balcony" },
        D: { angles: [ 16,  32], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
    };

    const COLORS = {
        available: { fill: "#22C55E", stroke: "#16A34A" },
        excluded:  { fill: "#EF4444", stroke: "#DC2626" },
        vip:       { fill: "#C9A530", stroke: "#A88729" },
    };

    const seats = {};
    const excludedKeys = new Set();
    let savedExcludedKeys = new Set();
    let totalNonVip = 0;
    let totalVip = 0;

    const seatsGroup = document.getElementById('seatsGroup');
    const labelsGroup = document.getElementById('labelsGroup');

    function buildSeats() {
        ["C", "B", "A", "F", "E", "D"].forEach(name => {
            const cfg = SECTIONS[name];
            const isBalc = cfg.floor === "balcony";
            const radiusStart = isBalc ? FAN.balcRadiusStart : FAN.orchRadiusStart;
            const rowGap = isBalc ? FAN.balcRowGap : FAN.orchRowGap;
            const [aStart, aEnd] = cfg.angles;

            // label للقسم - بعد آخر صف بشوية
            const labelAng = ((aStart + aEnd) / 2) * Math.PI / 180;
            const labelRadius = radiusStart + (cfg.rowSeats.length + 1) * rowGap;
            const labelX = FAN.cx + Math.sin(labelAng) * labelRadius;
            const labelY = FAN.cy + Math.cos(labelAng) * labelRadius;

            const labelBg = document.createElementNS(SVG_NS, "circle");
            labelBg.setAttribute("cx", labelX);
            labelBg.setAttribute("cy", labelY);
            labelBg.setAttribute("r", 32);
            labelBg.setAttribute("fill", "#0C4A6E");
            labelBg.setAttribute("opacity", "0.9");
            labelsGroup.appendChild(labelBg);

            const labelText = document.createElementNS(SVG_NS, "text");
            labelText.setAttribute("x", labelX);
            labelText.setAttribute("y", labelY + 12);
            labelText.setAttribute("text-anchor", "middle");
            labelText.setAttribute("fill", "#fff");
            labelText.setAttribute("font-weight", "700");
            labelText.setAttribute("font-size", "32");
            labelText.textContent = name;
            labelsGroup.appendChild(labelText);

            cfg.rowSeats.forEach((seatCount, rIdx) => {
                const r = rIdx + 1;
                const radius = radiusStart + rIdx * rowGap;
                // 🔧 VIP فقط الصف 10 من Orchestra (D, E, F البالكوني صارت عادية)
                const isVip = !isBalc && r === 10;

                for (let i = 0; i < seatCount; i++) {
                    const t = seatCount === 1 ? 0.5 : i / (seatCount - 1);
                    const angDeg = aStart + t * (aEnd - aStart);
                    const angRad = (angDeg * Math.PI) / 180;
                    const x = FAN.cx + Math.sin(angRad) * radius;
                    const y = FAN.cy + Math.cos(angRad) * radius;

                    // key بصيغة DB
                    const key = `${name}-${String(r).padStart(2, '0')}-${String(i+1).padStart(2, '0')}`;

                    const circle = document.createElementNS(SVG_NS, "circle");
                    circle.setAttribute("cx", x);
                    circle.setAttribute("cy", y);
                    circle.setAttribute("r", isBalc ? 14 : 13);
                    circle.setAttribute("data-key", key);
                    circle.setAttribute("data-section", name);

                    if (isVip) {
                        circle.style.fill = COLORS.vip.fill;
                        circle.style.stroke = COLORS.vip.stroke;
                        circle.style.cursor = "pointer";
                        circle.setAttribute("data-vip", "true");
                        circle.setAttribute("data-row", r);
                        circle.setAttribute("data-num", i+1);
                        totalVip++;

                        const title = document.createElementNS(SVG_NS, "title");
                        title.textContent = `${name}-${r}-${i+1} (VIP)`;
                        circle.appendChild(title);

                        // ✨ VIP: عرض معلومات فقط (لا استبعاد)
                        circle.addEventListener('click', () => showSeatInfo(name, r, i+1, 'vip'));
                    } else {
                        circle.style.fill = COLORS.available.fill;
                        circle.style.stroke = COLORS.available.stroke;
                        circle.style.cursor = "pointer";
                        circle.setAttribute("data-row", r);
                        circle.setAttribute("data-num", i+1);
                        totalNonVip++;

                        const title = document.createElementNS(SVG_NS, "title");
                        title.textContent = `${name}-${r}-${i+1}`;
                        circle.appendChild(title);

                        circle.addEventListener('click', () => {
                            toggleSeat(key);
                            showSeatInfo(name, r, i+1, excludedKeys.has(key) ? 'excluded' : 'available');
                        });
                    }
                    circle.style.strokeWidth = "1.5";
                    circle.style.transition = "all .15s";

                    seatsGroup.appendChild(circle);
                    seats[key] = circle;
                }
            });
        });

        console.log(`✓ Built ${Object.keys(seats).length} seats (${totalNonVip} non-VIP, ${totalVip} VIP)`);
    }

    // ✨ عرض معلومات المقعد في info box
    function showSeatInfo(section, row, num, status) {
        const box = document.getElementById('seatInfoBox');
        if (!box) return;

        const statusInfo = {
            available: { color: '#16A34A', bg: '#dcfce7', icon: 'bi-check-circle-fill', text: 'متاح للجمهور' },
            excluded:  { color: '#DC2626', bg: '#fef2f2', icon: 'bi-x-circle-fill', text: 'مستبعد (محجوز)' },
            vip:       { color: '#A88729', bg: '#fef9e7', icon: 'bi-shield-fill', text: 'VIP' },
        };
        const info = statusInfo[status] || statusInfo.available;

        box.style.background = info.bg;
        box.style.borderColor = info.color;
        box.style.color = info.color;
        box.innerHTML = `
            <span style="font-weight: 700; font-size: 14px;">
                <i class="bi ${info.icon}"></i>
                القسم ${section} — الصف ${row} — المقعد رقم ${num}
            </span>
            <span class="badge ms-2" style="background: ${info.color}; color: #fff; font-weight: 600;">
                ${info.text}
            </span>
        `;
    }

    function toggleSeat(key) {
        const seat = seats[key];
        if (!seat || seat.dataset.vip === 'true') return;

        if (excludedKeys.has(key)) {
            excludedKeys.delete(key);
            seat.style.fill = COLORS.available.fill;
            seat.style.stroke = COLORS.available.stroke;
        } else {
            excludedKeys.add(key);
            seat.style.fill = COLORS.excluded.fill;
            seat.style.stroke = COLORS.excluded.stroke;
        }
        updateStats();
        updateSaveButton();
    }

    function setSeatExcluded(key, excluded) {
        const seat = seats[key];
        if (!seat || seat.dataset.vip === 'true') return;

        if (excluded) {
            excludedKeys.add(key);
            seat.style.fill = COLORS.excluded.fill;
            seat.style.stroke = COLORS.excluded.stroke;
        } else {
            excludedKeys.delete(key);
            seat.style.fill = COLORS.available.fill;
            seat.style.stroke = COLORS.available.stroke;
        }
    }

    function updateStats() {
        const excluded = excludedKeys.size;
        const available = totalNonVip - excluded;
        document.getElementById('availableCount').textContent = available;
        document.getElementById('excludedCount').textContent = excluded;
    }

    function updateSaveButton() {
        const btn = document.getElementById('btnSave');
        const text = document.getElementById('saveText');
        const hasChanges = !areSetsEqual(excludedKeys, savedExcludedKeys);
        if (hasChanges) {
            text.textContent = 'حفظ التغييرات (غير محفوظة)';
            btn.classList.add('btn-warning');
            btn.classList.remove('btn-primary');
        } else {
            text.textContent = 'حفظ التغييرات';
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-warning');
        }
    }

    function areSetsEqual(a, b) {
        if (a.size !== b.size) return false;
        for (const x of a) if (!b.has(x)) return false;
        return true;
    }

    async function loadData() {
        try {
            const res = await fetch(API_GET, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('فشل التحميل: ' + res.status);
            const data = await res.json();

            (data.excluded_seat_keys || []).forEach(key => {
                if (seats[key] && seats[key].dataset.vip !== 'true') {
                    excludedKeys.add(key);
                    savedExcludedKeys.add(key);
                    seats[key].style.fill = COLORS.excluded.fill;
                    seats[key].style.stroke = COLORS.excluded.stroke;
                }
            });

            updateStats();
            updateSaveButton();
            document.getElementById('loadingOverlay').style.display = 'none';
        } catch (err) {
            console.error(err);
            alert('فشل تحميل بيانات المقاعد: ' + err.message);
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    }

    async function saveChanges() {
        const btn = document.getElementById('btnSave');
        const text = document.getElementById('saveText');
        btn.disabled = true;
        text.textContent = 'جارٍ الحفظ...';

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(API_SAVE, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ excluded_seat_keys: [...excludedKeys] })
            });

            if (!res.ok) throw new Error('فشل الحفظ: ' + res.status);
            const data = await res.json();

            savedExcludedKeys = new Set(excludedKeys);
            updateSaveButton();

            if (window.SwalHelper?.success) {
                SwalHelper.success(`تم حفظ التغييرات بنجاح (${data.excluded_count || excludedKeys.size} مقعد مستبعد)`);
            } else {
                alert('تم حفظ التغييرات بنجاح');
            }
        } catch (err) {
            console.error(err);
            alert('فشل الحفظ: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    }

    function includeAll() {
        Object.keys(seats).forEach(key => setSeatExcluded(key, false));
        updateStats();
        updateSaveButton();
    }

    function excludeAll() {
        Object.keys(seats).forEach(key => {
            if (seats[key].dataset.vip !== 'true') setSeatExcluded(key, true);
        });
        updateStats();
        updateSaveButton();
    }

    function toggleSection(section) {
        const sectionSeats = Object.entries(seats).filter(([k, s]) =>
            s.dataset.section === section && s.dataset.vip !== 'true'
        );
        const excludedInSection = sectionSeats.filter(([k]) => excludedKeys.has(k)).length;
        const allExcluded = excludedInSection === sectionSeats.length;

        sectionSeats.forEach(([k]) => setSeatExcluded(k, !allExcluded));
        updateStats();
        updateSaveButton();
    }

    function resetAll() {
        if (!confirm('إعادة تعيين سيلغي كل التغييرات غير المحفوظة. متأكدة؟')) return;
        excludedKeys.clear();
        Object.entries(seats).forEach(([key, seat]) => {
            if (seat.dataset.vip !== 'true') {
                seat.style.fill = COLORS.available.fill;
                seat.style.stroke = COLORS.available.stroke;
            }
        });
        savedExcludedKeys.forEach(key => {
            if (seats[key] && seats[key].dataset.vip !== 'true') {
                excludedKeys.add(key);
                seats[key].style.fill = COLORS.excluded.fill;
                seats[key].style.stroke = COLORS.excluded.stroke;
            }
        });
        updateStats();
        updateSaveButton();
    }

    // ربط الأحداث
    document.getElementById('btnSave').addEventListener('click', saveChanges);
    document.getElementById('btnResetAll').addEventListener('click', resetAll);
    document.getElementById('btnIncludeAll').addEventListener('click', includeAll);
    document.getElementById('btnExcludeAll').addEventListener('click', excludeAll);
    document.querySelectorAll('.section-toggle').forEach(btn => {
        btn.addEventListener('click', () => toggleSection(btn.dataset.section));
    });

    // البداية
    buildSeats();
    loadData();
})();
</script>
@endpush

</div>
