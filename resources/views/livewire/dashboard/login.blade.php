<div class="login-card">
    <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="شعار {{ config('theatre.university') }}"
             onerror="this.style.display='none'; document.getElementById('logo-fb').style.display='block';">
        <div id="logo-fb" style="display:none; font-size: 50px;">🎭</div>
        <h3>{{ config('theatre.name') }}</h3>
        <p>لوحة التحكم</p>
    </div>

    {{-- رسالة خطأ موحّدة (أنظف بصرياً) --}}
    @if($errorMessage)
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="login">
        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <div class="input-group-custom">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email"
                       wire:model="email"
                       class="form-control with-icon"
                       placeholder="username@uomosul.edu.iq"
                       autocomplete="email"
                       required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <div class="input-group-custom">
                <i class="bi bi-lock input-icon"></i>
                <input type="password"
                       wire:model="password"
                       class="form-control with-icon"
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required>
            </div>
        </div>

        <button type="submit" class="btn btn-login" wire:loading.attr="disabled">
            <span wire:loading.remove>
                <i class="bi bi-box-arrow-in-left"></i> تسجيل الدخول
            </span>
            <span wire:loading>
                <span class="wire-loading"></span> جاري تسجيل الدخول...
            </span>
        </button>
    </form>
</div>
