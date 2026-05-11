<div class="seat-availability-page">

{{-- ═══ شريط الإحصائيات والإجراءات ═══ --}}
<div class="card-custom p-3 mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-7">
            <h5 class="mb-1" style="color: var(--primary);">
                <i class="bi bi-grid-3x3-gap-fill" style="color: #15803D;"></i>
                تحديد المقاعد المتاحة للجمهور
            </h5>
            <small class="text-muted">
                <strong style="color: #0C4A6E;">{{ $event->title }}</strong>
                — اضغطي على المقعد لاستبعاده/إعادة إتاحته
            </small>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('dashboard.events') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-right"></i> رجوع
            </a>
            <button type="button" id="btnReset" class="btn btn-sm btn-outline-warning me-2">
                <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
            </button>
            <button type="button" id="btnSave" class="btn btn-sm" style="background: linear-gradient(135deg, #15803D, #166534); color: #fff; font-weight: 700; padding: 8px 18px;">
                <i class="bi bi-save-fill"></i>
                <span id="saveText">حفظ التغييرات</span>
            </button>
        </div>
    </div>
</div>

{{-- ═══ الإحصائيات اللحظية ═══ --}}
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card-custom p-3 text-center" style="border-right: 4px solid #15803D;">
            <h3 class="mb-0" style="color: #15803D; font-weight: 800;" id="statAvailable">0</h3>
            <small class="text-muted">✅ متاح للجمهور</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3 text-center" style="border-right: 4px solid #DC2626;">
            <h3 class="mb-0" style="color: #DC2626; font-weight: 800;" id="statExcluded">0</h3>
            <small class="text-muted">⛔ مستبعد (يظهر محجوز)</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3 text-center" style="border-right: 4px solid #0C4A6E;">
            <h3 class="mb-0" style="color: #0C4A6E; font-weight: 800;" id="statTotal">0</h3>
            <small class="text-muted">📊 إجمالي المقاعد</small>
        </div>
    </div>
</div>

{{-- ═══ أدوات سريعة ═══ --}}
<div class="card-custom p-3 mb-3">
    <small class="text-muted d-block mb-2">
        <i class="bi bi-lightning-charge-fill" style="color: #f59e0b;"></i>
        أدوات سريعة:
    </small>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkAction('all_available')">
            <i class="bi bi-check2-all"></i> إتاحة الكل
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkAction('all_excluded')">
            <i class="bi bi-x-octagon"></i> استبعاد الكل
        </button>
        <span class="vr"></span>
        <small class="align-self-center text-muted me-2">استبعاد قسم:</small>
        @foreach(['A', 'B', 'C', 'D', 'E', 'F'] as $sec)
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="excludeSection('{{ $sec }}')">
            {{ $sec }}
        </button>
        @endforeach
    </div>
</div>

{{-- ═══ الخريطة ═══ --}}
<div class="card-custom p-2">
    <div id="mapWrapper" style="position: relative; background: linear-gradient(180deg, #F8FAFC, #EEF2F7); border-radius: 12px; padding: 20px; min-height: 600px;">

        {{-- خشبة المسرح --}}
        <div style="text-align: center; margin-bottom: 12px;">
            <div style="display: inline-block; background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff; padding: 12px 60px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(12,74,110,0.3);">
                🎭 خشبة المسرح
            </div>
        </div>

        {{-- SVG container --}}
        <div style="overflow: auto;">
            <svg id="mapSvg" width="100%" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet" style="max-height: 600px;">
                <g id="seatsGroup"></g>
                <g id="labelsGroup"></g>
            </svg>
        </div>

        {{-- مؤشر تحميل --}}
        <div id="loadingOverlay" style="position: absolute; inset: 0; background: rgba(255,255,255,0.85); display: flex; align-items: center; justify-content: center; border-radius: 12px;">
            <div style="text-align: center;">
                <div class="spinner-border" style="color: #0C4A6E; width: 50px; height: 50px;"></div>
                <p class="mt-3 mb-0" style="color: #0C4A6E; font-weight: 600;">جارٍ تحميل الخريطة...</p>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap" style="font-size: 13px;">
        <span><span style="display: inline-block; width: 16px; height: 16px; background: #22C55E; border-radius: 4px; vertical-align: middle;"></span> متاح للجمهور</span>
        <span><span style="display: inline-block; width: 16px; height: 16px; background: #EF4444; border-radius: 4px; vertical-align: middle;"></span> مستبعد (يظهر محجوزاً)</span>
    </div>
</div>

{{-- ═══ JavaScript ═══ --}}
@push('scripts')
<script>
(function() {
    'use strict';

    const EVENT_UUID = @json($event->uuid);
    const API_GET = `/api/events/${EVENT_UUID}/availability`;
    const API_SAVE = `/api/events/${EVENT_UUID}/availability/save`;

    const SVG_NS = "http://www.w3.org/2000/svg";
    const FAN = { cx: 500, cy: 750, orchRadiusStart: 130, orchRowGap: 28, balcRadiusStart: 540, balcRowGap: 26 };

    const SECTIONS = {
        C: { angles: [-34, -16], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
        B: { angles: [-12,  12], rowSeats: [13,14,14,15,15,16,16,17,17,18,18,19,19,20,20], floor: "orchestra" },
        A: { angles: [ 16,  34], rowSeats: [9,10,10,11,11,12,12,13,13,14,14,15,15,16,16], floor: "orchestra" },
        F: { angles: [-32, -16], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
        E: { angles: [-12,  12], rowSeats: [16,16,17,17,18,18,19,19], floor: "balcony" },
        D: { angles: [ 16,  32], rowSeats: [12,13,13,14,14,15,15,16], floor: "balcony" },
    };

    const seats = {};
    const excludedKeys = new Set();
    let savedExcludedKeys = new Set();
    let totalSeats = 0;
    const seatsGroup = document.getElementById('seatsGroup');
    const labelsGroup = document.getElementById('labelsGroup');

    function buildSeats() {
        let totalCount = 0;

        ["C", "B", "A", "F", "E", "D"].forEach(name => {
            const cfg = SECTIONS[name];
            const isBalc = cfg.floor === "balcony";
            const radiusStart = isBalc ? FAN.balcRadiusStart : FAN.orchRadiusStart;
            const rowGap = isBalc ? FAN.balcRowGap : FAN.orchRowGap;
            const [aStart, aEnd] = cfg.angles;

            cfg.rowSeats.forEach((seatCount, rIdx) => {
                const r = rIdx + 1;
                const radius = radiusStart + rIdx * rowGap;

                for (let i = 0; i < seatCount; i++) {
                    const t = seatCount === 1 ? 0.5 : i / (seatCount - 1);
                    const angDeg = aStart + t * (aEnd - aStart);
                    const angRad = (angDeg * Math.PI) / 180;
                    const x = FAN.cx + Math.sin(angRad) * radius;
                    const y = FAN.cy + Math.cos(angRad) * radius;
                    const key = `${name}-${r}-${i+1}`;

                    const rect = document.createElementNS(SVG_NS, "rect");
                    rect.setAttribute("x", x - 8);
                    rect.setAttribute("y", y - 8);
                    rect.setAttribute("width", 16);
                    rect.setAttribute("height", 16);
                    rect.setAttribute("rx", 3);
                    rect.setAttribute("data-key", key);
                    rect.style.cursor = "pointer";
                    rect.style.fill = "#22C55E";  // كل المقاعد خضراء (متاحة) افتراضياً
                    rect.style.stroke = "#fff";
                    rect.style.strokeWidth = "1";
                    rect.style.transition = "fill .15s, transform .15s";
                    rect.style.transformOrigin = `${x}px ${y}px`;

                    rect.addEventListener('click', () => toggleSeat(key));
                    rect.addEventListener('mouseenter', () => rect.style.transform = `scale(1.4)`);
                    rect.addEventListener('mouseleave', () => rect.style.transform = ``);

                    rect.innerHTML = `<title>${key}</title>`;
                    seatsGroup.appendChild(rect);
                    seats[key] = rect;
                    totalCount++;
                }
            });

            // Section label
            const aMid = (cfg.angles[0] + cfg.angles[1]) / 2;
            const aRad = (aMid * Math.PI) / 180;
            const labelRadius = radiusStart - 50;
            const lx = FAN.cx + Math.sin(aRad) * labelRadius;
            const ly = FAN.cy + Math.cos(aRad) * labelRadius;

            const labelBg = document.createElementNS(SVG_NS, "rect");
            labelBg.setAttribute("x", lx - 24);
            labelBg.setAttribute("y", ly - 18);
            labelBg.setAttribute("width", 48);
            labelBg.setAttribute("height", 36);
            labelBg.setAttribute("rx", 8);
            labelBg.style.fill = "rgba(12,74,110,0.9)";
            labelsGroup.appendChild(labelBg);

            const label = document.createElementNS(SVG_NS, "text");
            label.setAttribute("x", lx);
            label.setAttribute("y", ly + 5);
            label.setAttribute("text-anchor", "middle");
            label.style.fill = "#fff";
            label.style.fontWeight = "700";
            label.style.fontSize = "16px";
            label.textContent = name;
            labelsGroup.appendChild(label);
        });

        totalSeats = totalCount;
        document.getElementById('statTotal').textContent = totalCount;
        console.log(`✓ Built ${totalCount} seats`);
    }

    function toggleSeat(key) {
        const rect = seats[key];
        if (!rect) return;

        if (excludedKeys.has(key)) {
            excludedKeys.delete(key);
            rect.style.fill = "#22C55E";
        } else {
            excludedKeys.add(key);
            rect.style.fill = "#EF4444";
        }
        updateStats();
    }

    function updateStats() {
        const excluded = excludedKeys.size;
        document.getElementById('statExcluded').textContent = excluded;
        document.getElementById('statAvailable').textContent = (totalSeats - excluded);

        const hasChanges = !areSetsEqual(excludedKeys, savedExcludedKeys);
        const btnSave = document.getElementById('btnSave');
        const saveText = document.getElementById('saveText');
        if (hasChanges) {
            saveText.textContent = 'حفظ التغييرات (غير محفوظة)';
            btnSave.style.animation = 'sa-pulse 1.5s infinite';
        } else {
            saveText.textContent = 'حفظ التغييرات';
            btnSave.style.animation = '';
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
            if (!res.ok) throw new Error('فشل التحميل');
            const data = await res.json();

            (data.excluded_seat_keys || []).forEach(key => {
                excludedKeys.add(key);
                savedExcludedKeys.add(key);
                if (seats[key]) {
                    seats[key].style.fill = "#EF4444";
                }
            });

            updateStats();
            document.getElementById('loadingOverlay').style.display = 'none';
        } catch (err) {
            console.error(err);
            alert('فشل تحميل بيانات المقاعد: ' + err.message);
        }
    }

    async function saveChanges() {
        const btnSave = document.getElementById('btnSave');
        const saveText = document.getElementById('saveText');
        btnSave.disabled = true;
        saveText.textContent = 'جارٍ الحفظ...';

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.content : '';

            const res = await fetch(API_SAVE, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ excluded_keys: Array.from(excludedKeys) })
            });
            if (!res.ok) throw new Error('فشل الحفظ');
            const data = await res.json();

            savedExcludedKeys = new Set(excludedKeys);
            updateStats();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ',
                    text: data.message || 'تم حفظ التغييرات بنجاح',
                    timer: 2000,
                });
            } else {
                alert('تم الحفظ بنجاح');
            }
        } catch (err) {
            console.error(err);
            alert('فشل الحفظ: ' + err.message);
        } finally {
            btnSave.disabled = false;
            saveText.textContent = 'حفظ التغييرات';
        }
    }

    function resetChanges() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'إعادة التعيين؟',
                text: 'سيتم استعادة آخر حالة محفوظة',
                showCancelButton: true,
                confirmButtonText: 'نعم، أعيدي',
                cancelButtonText: 'إلغاء',
            }).then(result => {
                if (result.isConfirmed) doReset();
            });
        } else if (confirm('سيتم استعادة آخر حالة محفوظة. متابعة؟')) {
            doReset();
        }
    }

    function doReset() {
        excludedKeys.clear();
        savedExcludedKeys.forEach(k => excludedKeys.add(k));
        Object.entries(seats).forEach(([key, rect]) => {
            rect.style.fill = excludedKeys.has(key) ? "#EF4444" : "#22C55E";
        });
        updateStats();
    }

    window.bulkAction = function(action) {
        if (action === 'all_available') {
            excludedKeys.clear();
        } else if (action === 'all_excluded') {
            Object.keys(seats).forEach(key => excludedKeys.add(key));
        }
        Object.entries(seats).forEach(([key, rect]) => {
            rect.style.fill = excludedKeys.has(key) ? "#EF4444" : "#22C55E";
        });
        updateStats();
    };

    window.excludeSection = function(sectionName) {
        Object.keys(seats).forEach(key => {
            if (key.startsWith(sectionName + '-')) {
                excludedKeys.add(key);
                seats[key].style.fill = "#EF4444";
            }
        });
        updateStats();
    };

    function init() {
        buildSeats();
        loadData();
        document.getElementById('btnSave').addEventListener('click', saveChanges);
        document.getElementById('btnReset').addEventListener('click', resetChanges);
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
</script>

<style>
@keyframes sa-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
</style>
@endpush

</div>
