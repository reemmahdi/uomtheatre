<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'لوحة التحكم' }} — {{ config('theatre.name') }}</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Dashboard Styles --}}
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

    {{-- SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/sweet-alert-custom.css') }}">

    @livewireStyles
</head>
<body>
    @php $roleName = auth()->user()->role->name; @endphp

    {{-- Mobile Overlay --}}
    <div class="mobile-overlay" id="mobileOverlay"></div>

    {{-- Sidebar --}}
    <div class="sidebar" id="mainSidebar">

        {{-- زر إغلاق - فقط على الجوال --}}
        <button class="mobile-close-btn d-md-none" id="mobileCloseBtn" aria-label="إغلاق">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- زر طي السايدبار - فقط على الكمبيوتر --}}
        <button class="sidebar-toggle d-none d-md-flex" id="sidebarToggle" title="طي/فتح القائمة" aria-label="طي/فتح القائمة">
            <span class="hamburger-box">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </span>
        </button>

        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="شعار"
                 onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block';">
            <div id="logo-fallback" style="display:none; font-size: 40px;">🎭</div>
            <div class="logo-text">
                <h5>{{ config('theatre.name') }}</h5>
                <small>{{ config('theatre.hall_name', 'نظام حجز مقاعد المسرح') }}</small>
                <br><span class="role-badge">{{ auth()->user()->role->display_name }}</span>
            </div>
        </div>

        <nav class="mt-3">
            <a href="{{ route('dashboard') }}" data-title="الرئيسية" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span class="nav-text">الرئيسية</span>
            </a>

            @if($roleName === 'super_admin')
            <a href="{{ route('dashboard.users') }}" data-title="إدارة المستخدمين" class="nav-link {{ request()->routeIs('dashboard.users') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span class="nav-text">إدارة المستخدمين</span>
            </a>
            <a href="{{ route('dashboard.staff') }}" data-title="إدارة الموظفين" class="nav-link {{ request()->routeIs('dashboard.staff') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i><span class="nav-text">إدارة الموظفين</span>
            </a>
            @endif

            @if(in_array($roleName, ['super_admin', 'theater_manager', 'event_manager']))
            <a href="{{ route('dashboard.events') }}" data-title="الفعاليات" class="nav-link {{ request()->routeIs('dashboard.events') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i><span class="nav-text">الفعاليات</span>
            </a>
            @endif

            @if(in_array($roleName, ['super_admin', 'event_manager']))
            <a href="{{ route('dashboard.vip-events') }}" data-title="مقاعد الوفود" class="nav-link {{ request()->routeIs('dashboard.vip-events') || request()->routeIs('dashboard.vip-booking') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i><span class="nav-text">مقاعد الوفود ({{ config('theatre.vip_seats') }})</span>
            </a>
            @endif

            @if(in_array($roleName, ['super_admin', 'receptionist', 'event_manager']))
            <a href="{{ route('dashboard.seats-display') }}" data-title="شاشة العرض" class="nav-link {{ request()->routeIs('dashboard.seats-display') ? 'active' : '' }}">
                <i class="bi bi-display"></i><span class="nav-text">شاشة العرض المباشر</span>
            </a>
            @endif

            @if(in_array($roleName, ['super_admin', 'receptionist']))
            <a href="{{ route('dashboard.checkin') }}" data-title="تسجيل الحضور" class="nav-link {{ request()->routeIs('dashboard.checkin') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i><span class="nav-text">تسجيل الحضور</span>
            </a>
            @endif

            @if(in_array($roleName, ['super_admin', 'university_office']))
            <a href="{{ route('dashboard.stats') }}" data-title="الإحصائيات" class="nav-link {{ request()->routeIs('dashboard.stats') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i><span class="nav-text">الإحصائيات</span>
            </a>
            @endif

            <hr style="border-color: rgba(228, 192, 94, 0.25); margin: 15px 20px;">
            <form method="POST" action="{{ route('dashboard.logout') }}" class="mx-3">
                @csrf
                <button type="submit" data-title="تسجيل خروج" class="nav-link w-100 text-start border-0 bg-transparent" style="cursor:pointer; color: #ffcdd2;">
                    <i class="bi bi-box-arrow-right"></i><span class="nav-text">تسجيل خروج</span>
                </button>
            </form>
        </nav>
    </div>

    <div class="main-content" id="mainContent">
        <div class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="topbar-toggle" id="topbarToggle" title="القائمة" aria-label="القائمة">
                    <span class="hamburger-box">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </span>
                </button>
                <h5 class="mb-0">{{ $title ?? 'لوحة التحكم' }}</h5>
            </div>
            <span class="text-muted topbar-user-info">
                <i class="bi bi-person-circle" style="color: var(--primary);"></i>
                <span class="topbar-username">{{ auth()->user()->name }}</span>
                <span class="badge ms-2">{{ auth()->user()->role->display_name }}</span>
            </span>
        </div>

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/sweet-alert-helper.js') }}"></script>

    <script>
        (function() {
            const sidebar = document.getElementById('mainSidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const topbarToggle = document.getElementById('topbarToggle');
            const mobileCloseBtn = document.getElementById('mobileCloseBtn');
            const mobileOverlay = document.getElementById('mobileOverlay');

            function isMobile() {
                return window.innerWidth <= 768;
            }

            // ═══ سلوك الكمبيوتر ═══
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && !isMobile()) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                if (sidebarToggle) sidebarToggle.classList.add('is-active');
                if (topbarToggle) topbarToggle.classList.add('is-active');
            }

            function toggleDesktopSidebar() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                if (sidebarToggle) sidebarToggle.classList.toggle('is-active');
                if (topbarToggle) topbarToggle.classList.toggle('is-active');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }

            // ═══ سلوك الجوال ═══
            function openMobileSidebar() {
                sidebar.classList.add('mobile-open');
                mobileOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('mobile-open');
                mobileOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // ═══ Event Listeners ═══
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', () => {
                    if (!isMobile()) toggleDesktopSidebar();
                });
            }

            if (topbarToggle) {
                topbarToggle.addEventListener('click', () => {
                    if (isMobile()) {
                        openMobileSidebar();
                    } else {
                        toggleDesktopSidebar();
                    }
                });
            }

            if (mobileCloseBtn) {
                mobileCloseBtn.addEventListener('click', closeMobileSidebar);
            }

            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', closeMobileSidebar);
            }

            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (isMobile()) closeMobileSidebar();
                });
            });

            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (!isMobile()) {
                        sidebar.classList.remove('mobile-open');
                        mobileOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    } else {
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('expanded');
                    }
                }, 200);
            });
        })();
    </script>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                SwalHelper.success(@json(session('success')));
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                SwalHelper.error(@json(session('error')));
            });
        </script>
    @endif

    @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                SwalHelper.warning(@json(session('warning')));
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
