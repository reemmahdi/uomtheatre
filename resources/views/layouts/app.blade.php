<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- ✨ مُصحَّح: CSRF meta tag (مطلوب للـ AJAX/fetch مثل seat-availability) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ✨ عنوان مخصص لتبويب المتصفح بناءً على الصفحة الحالية --}}
    @php
        $browserTitle = $title ?? match(request()->route()?->getName()) {
            'dashboard'                            => 'الرئيسية',
            'dashboard.events'                     => 'إدارة الفعاليات',
            'dashboard.event-approvals'            => 'الفعاليات بانتظار موافقتي',
            'dashboard.vip-events'                 => 'إدارة حجز مقاعد الضيوف',
            'dashboard.vip-booking'                => 'إدارة حجز مقاعد الضيوف',
            'dashboard.event-cancellation-notices' => 'إشعارات إلغاء الفعالية',
            'dashboard.users'                      => 'إدارة المستخدمين',
            'dashboard.staff'                      => 'إدارة الموظفين',
            'dashboard.permissions'                => 'إدارة الصلاحيات',
            'dashboard.checkin'                    => 'تسجيل الحضور',
            'dashboard.seats-display'              => 'شاشة العرض المباشر',
            'dashboard.stats'                      => 'الإحصائيات',
            default                                => 'لوحة التحكم',
        };
    @endphp

    <title>{{ $browserTitle }} — {{ config('theatre.name') }}</title>

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

    {{-- Flatpickr - مكتبة تواريخ وأوقات احترافية --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css">

    @livewireStyles
</head>
<body>
    {{-- ✨ مُصحَّح: nullsafe على role + early redirect لو null --}}
    @php
        $authUser = auth()->user();
        $roleName = $authUser?->role?->name;
        $roleDisplayName = $authUser?->role?->display_name ?? 'مستخدم';

        // لو ما عنده role صحيح، يُسجَّل خروج (الـ middleware يفترض يحمي قبل، لكن للأمان)
        if (!$roleName) {
            auth()->logout();
            return redirect()->route('login')->send();
        }
    @endphp

    {{-- Mobile Overlay --}}
    <div class="mobile-overlay" id="mobileOverlay"></div>

    {{-- Sidebar --}}
    <div class="sidebar" id="mainSidebar">

        {{-- زر إغلاق - فقط على الجوال --}}
        <button class="mobile-close-btn d-md-none" id="mobileCloseBtn" aria-label="إغلاق">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="logo">
            <img src="{{ asset('images/logo.png') }}" alt="شعار"
                 onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block';">
            <div id="logo-fallback" style="display:none; font-size: 40px;">🎭</div>
            <div class="logo-text">
                <h5>{{ config('theatre.name') }}</h5>
                <small>{{ config('theatre.hall_name', 'نظام حجز مقاعد المسرح') }}</small>
                <br><span class="role-badge">{{ $roleDisplayName }}</span>
            </div>
        </div>

        <nav class="mt-3">
            <a href="{{ route('dashboard') }}" data-title="الرئيسية" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span class="nav-text">الرئيسية</span>
            </a>

            @if($roleName === \App\Models\Role::SUPER_ADMIN)
            <a href="{{ route('dashboard.users') }}" data-title="إدارة المستخدمين" class="nav-link {{ request()->routeIs('dashboard.users') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span class="nav-text">إدارة المستخدمين</span>
            </a>
            <a href="{{ route('dashboard.staff') }}" data-title="إدارة الموظفين" class="nav-link {{ request()->routeIs('dashboard.staff') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i><span class="nav-text">إدارة الموظفين</span>
            </a>
            <a href="{{ route('dashboard.permissions') }}" data-title="إدارة الصلاحيات" class="nav-link {{ request()->routeIs('dashboard.permissions') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i><span class="nav-text">إدارة الصلاحيات</span>
            </a>
            @endif

            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::THEATER_MANAGER, \App\Models\Role::EVENT_MANAGER], true))
            <a href="{{ route('dashboard.events') }}" data-title="إدارة الفعاليات" class="nav-link {{ request()->routeIs('dashboard.events') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i><span class="nav-text">إدارة الفعاليات</span>
            </a>
            @endif

            {{-- شاشة الموافقات (مدير المسرح + مكتب الرئيس + سوبر أدمن) --}}
            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::THEATER_MANAGER, \App\Models\Role::UNIVERSITY_OFFICE], true))
            @php
                // ✨ مُحسَّن: cache لمدة دقيقة لتجنب query في كل request
                $cacheKey = 'pending_approvals_count_' . ($authUser->id);
                $pendingApprovalsCount = \Illuminate\Support\Facades\Cache::remember(
                    $cacheKey,
                    now()->addMinute(),
                    function () use ($roleName, $authUser) {
                        return \App\Models\EventApproval::query()
                            ->when(
                                $roleName !== \App\Models\Role::SUPER_ADMIN,
                                fn($q) => $q->where('role_id', $authUser->role_id)
                            )
                            ->where('status', \App\Models\EventApproval::STATUS_PENDING)
                            ->count();
                    }
                );
            @endphp
            <a href="{{ route('dashboard.event-approvals') }}" data-title="بانتظار موافقتي" class="nav-link {{ request()->routeIs('dashboard.event-approvals') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check-fill"></i>
                <span class="nav-text">
                    بانتظار موافقتي
                    @if($pendingApprovalsCount > 0)
                    <span class="badge rounded-pill" style="background: #DC2626; color: #fff; margin-right: 6px; font-size: 11px;">
                        {{ $pendingApprovalsCount }}
                    </span>
                    @endif
                </span>
            </a>
            @endif

            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::EVENT_MANAGER], true))
            <a href="{{ route('dashboard.vip-events') }}" data-title="إدارة حجز مقاعد الضيوف" class="nav-link {{ request()->routeIs('dashboard.vip-events') || request()->routeIs('dashboard.vip-booking') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i><span class="nav-text">إدارة حجز مقاعد الضيوف</span>
            </a>
            @endif

            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::RECEPTIONIST, \App\Models\Role::THEATER_MANAGER], true))
            <a href="{{ route('dashboard.seats-display') }}" data-title="شاشة العرض" class="nav-link {{ request()->routeIs('dashboard.seats-display') ? 'active' : '' }}">
                <i class="bi bi-display"></i><span class="nav-text">شاشة العرض المباشر</span>
            </a>
            @endif

            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::RECEPTIONIST], true))
            <a href="{{ route('dashboard.checkin') }}" data-title="تسجيل الحضور" class="nav-link {{ request()->routeIs('dashboard.checkin') ? 'active' : '' }}">
                <i class="bi bi-qr-code-scan"></i><span class="nav-text">تسجيل الحضور</span>
            </a>
            @endif

            @if(in_array($roleName, [\App\Models\Role::SUPER_ADMIN, \App\Models\Role::UNIVERSITY_OFFICE], true))
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
                <h5 class="mb-0">
                    {{ $browserTitle }}
                </h5>
            </div>
            <div class="d-flex align-items-center" style="gap: 8px;">
                {{-- جرس الإشعارات --}}
                <livewire:notifications-bell />

                <span class="text-muted topbar-user-info">
                    <i class="bi bi-person-circle" style="color: var(--primary);"></i>
                    <span class="topbar-username">{{ $authUser->name }}</span>
                    <span class="badge ms-2">{{ $roleDisplayName }}</span>
                </span>
            </div>
        </div>

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Alpine.js للـ dropdown الخاص بالجرس --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/sweet-alert-helper.js') }}"></script>

    {{-- Flatpickr - مكتبة تواريخ وأوقات احترافية --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ar.js"></script>

    <script>
        (function() {
            const sidebar = document.getElementById('mainSidebar');
            const mainContent = document.getElementById('mainContent');
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
                if (topbarToggle) topbarToggle.classList.add('is-active');
            }

            function toggleDesktopSidebar() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
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
