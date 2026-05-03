<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'لوحة التحكم' }} — {{ config('theatre.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    @livewireStyles
</head>
<body>
    @php $roleName = auth()->user()->role->name; @endphp

    <div class="sidebar">
        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="شعار"
                 onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block';">
            <div id="logo-fallback" style="display:none; font-size: 40px;">🎭</div>
            <h5>{{ config('theatre.name') }}</h5>
            <small>{{ config('theatre.hall_name', 'نظام حجز مقاعد المسرح') }}</small>
            <br><span class="role-badge">{{ auth()->user()->role->display_name }}</span>
        </div>
        <nav class="mt-3">
            {{-- 1. الرئيسية — الكل --}}
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> الرئيسية
            </a>

            {{-- 2. إدارة المستخدمين + الموظفين — super_admin فقط --}}
            @if($roleName === 'super_admin')
            <a href="{{ route('dashboard.users') }}" class="nav-link {{ request()->routeIs('dashboard.users') ? 'active' : '' }}">
                <i class="bi bi-people"></i> إدارة المستخدمين
            </a>
            <a href="{{ route('dashboard.staff') }}" class="nav-link {{ request()->routeIs('dashboard.staff') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> إدارة الموظفين
            </a>
            @endif

            {{-- 3. الفعاليات — super_admin + theater_manager + event_manager --}}
            @if(in_array($roleName, ['super_admin', 'theater_manager', 'event_manager']))
            <a href="{{ route('dashboard.events') }}" class="nav-link {{ request()->routeIs('dashboard.events') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> الفعاليات
            </a>
            @endif

            {{-- 4. مقاعد الوفود — super_admin + event_manager --}}
            @if(in_array($roleName, ['super_admin', 'event_manager']))
            <a href="{{ route('dashboard.vip-events') }}" class="nav-link {{ request()->routeIs('dashboard.vip-events') || request()->routeIs('dashboard.vip-booking') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i> مقاعد الوفود ({{ config('theatre.vip_seats') }})
            </a>
            @endif

            {{-- 5. تسجيل الحضور — super_admin + receptionist --}}
            @if(in_array($roleName, ['super_admin', 'receptionist']))
            <a href="{{ route('dashboard.checkin') }}" class="nav-link {{ request()->routeIs('dashboard.checkin') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i> تسجيل الحضور
            </a>
            @endif

            {{-- 6. الإحصائيات — super_admin + university_office --}}
            @if(in_array($roleName, ['super_admin', 'university_office']))
            <a href="{{ route('dashboard.stats') }}" class="nav-link {{ request()->routeIs('dashboard.stats') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> الإحصائيات
            </a>
            @endif

            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 20px;">
            <form method="POST" action="{{ route('dashboard.logout') }}" class="mx-3">
                @csrf
                <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent" style="cursor:pointer;">
                    <i class="bi bi-box-arrow-right"></i> تسجيل خروج
                </button>
            </form>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h5>{{ $title ?? 'لوحة التحكم' }}</h5>
            <span class="text-muted">
                <i class="bi bi-person-circle" style="color: var(--primary);"></i>
                {{ auth()->user()->name }}
                <span class="badge ms-2">{{ auth()->user()->role->display_name }}</span>
            </span>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
