<div>
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
        </div>
    </div>
</div>
</div>
