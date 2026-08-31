<div wire:poll.15s>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-custom p-4">
            <div class="text-center mb-4">
                <i class="bi bi-qr-code-scan" style="font-size:60px;color:#0C4A6E;"></i>
                <h4 class="mt-2" style="color:#0C4A6E;">مسح رمز QR</h4>
            </div>

            @if($message)
            <div class="alert alert-{{ $messageType }} text-center">
                <strong>{{ $message }}</strong>
            </div>
            @endif

            @if(!empty($checkInData))
            <div class="alert alert-success">
                <div class="row text-center">
                    <div class="col-6 mb-2"><small class="text-muted">الاسم</small><br><strong>{{ $checkInData['name'] }}</strong></div>
                    <div class="col-6 mb-2"><small class="text-muted">الفعالية</small><br><strong>{{ $checkInData['event'] }}</strong></div>
                    <div class="col-4"><small class="text-muted">القسم</small><br><strong>{{ $checkInData['section'] }}</strong></div>
                    <div class="col-4"><small class="text-muted">المقعد</small><br><strong>{{ $checkInData['seat'] }}</strong></div>
                    <div class="col-4"><small class="text-muted">النوع</small><br><strong>{{ $checkInData['type'] }}</strong></div>
                </div>
            </div>
            @endif

            <div class="input-group input-group-lg">
                <input type="text" wire:model="qrCode" wire:keydown.enter="scan" class="form-control" placeholder="أدخل أو امسح رمز QR هنا..." autofocus>
                <button wire:click="scan" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="scan"><i class="bi bi-search"></i> تحقق</span>
                    <span wire:loading wire:target="scan"><span class="wire-loading"></span></span>
                </button>
            </div>
            <small class="text-muted mt-2 d-block text-center">امسح الرمز بالماسح أو أدخله يدوياً واضغط Enter</small>

            {{-- ============ المسح بكاميرا الجهاز ============ --}}
            <div class="text-center mt-3" wire:ignore>
                <button type="button" id="cameraBtn" class="btn btn-outline-primary btn-sm"
                        onclick="uomToggleCamera()">
                    <i class="bi bi-camera-video"></i> تشغيل الكاميرا
                </button>
                <div id="cameraNote" class="alert alert-warning mt-2 mb-0 py-2 small" style="display:none;">
                    المتصفح يمنع الكاميرا على هذا العنوان (يتطلب https) —
                    استخدمي الماسح اليدوي أو الإدخال المباشر
                </div>
                <div id="qr-reader" class="mx-auto mt-3" style="max-width: 320px;"></div>
            </div>
        </div>

        {{-- ============ آخر التذاكر المفحوصة ============ --}}
        <div class="card-custom p-4 mt-4">
            <h5 class="mb-3" style="color:#0C4A6E;">
                <i class="bi bi-list-check"></i>
                آخر التذاكر المفحوصة
                <span class="badge bg-secondary">{{ $recentScans->count() }}</span>
            </h5>

            @if($recentScans->isEmpty())
                <p class="text-muted text-center mb-0">لم تُفحص أي تذكرة بعد</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th>#</th>
                                <th>الاسم</th>
                                <th>الفعالية</th>
                                <th>المقعد</th>
                                <th>النوع</th>
                                <th>وقت الدخول</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentScans as $i => $scan)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><strong>{{ ($scan->type === 'vip_guest' ? ($scan->guest_name ?? 'وفد') : ($scan->user?->name ?? '—')) ?? $scan->guest_name ?? 'ضيف' }}</strong></td>
                                <td class="small">{{ $scan->event?->title ?? '—' }}</td>
                                <td>{{ $scan->seat?->label ?? '—' }}</td>
                                <td>
                                    @if($scan->type === 'vip_guest')
                                        <span class="badge" style="background:#C9A530;">وفود</span>
                                    @else
                                        <span class="badge bg-primary">عادي</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $scan->updated_at?->format('h:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@assets
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endassets

@script
<script>
    let uomScanner = null;
    let uomLastScan = 0;

    window.uomToggleCamera = async () => {
        const btn = document.getElementById('cameraBtn');
        const note = document.getElementById('cameraNote');

        // إيقاف إن كانت شغالة
        if (uomScanner) {
            try { await uomScanner.stop(); uomScanner.clear(); } catch (e) {}
            uomScanner = null;
            btn.innerHTML = '<i class="bi bi-camera-video"></i> تشغيل الكاميرا';
            return;
        }

        // الكاميرا تتطلب سياقاً آمناً (https أو localhost)
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            note.style.display = 'block';
            return;
        }

        uomScanner = new Html5Qrcode('qr-reader');
        try {
            await uomScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                (decodedText) => {
                    // منع التكرار: مهلة ثانيتين ونصف بين مسحة وأخرى
                    const now = Date.now();
                    if (now - uomLastScan < 2500) return;
                    uomLastScan = now;
                    $wire.set('qrCode', decodedText);
                    $wire.call('scan');
                }
            );
            btn.innerHTML = '<i class="bi bi-stop-circle"></i> إيقاف الكاميرا';
        } catch (e) {
            uomScanner = null;
            note.style.display = 'block';
        }
    };
</script>
@endscript
</div>
