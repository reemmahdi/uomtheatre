<div class="wa-bell-wrapper" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">

    {{-- ─── أيقونة الجرس ─── --}}
    <button type="button"
            class="wa-bell-btn"
            @click="open = !open"
            aria-label="الإشعارات">
        <i class="bi bi-bell-fill"></i>
        @if($unreadCount > 0)
        <span class="wa-bell-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    {{-- ─── القائمة المنسدلة ─── --}}
    <div class="wa-bell-dropdown" x-show="open" x-cloak x-transition.opacity.duration.150ms @click.stop>

        {{-- رأس القائمة --}}
        <div class="wa-bell-header">
            <div class="wa-bell-title">
                <i class="bi bi-bell-fill"></i>
                <span>الإشعارات</span>
                @if($unreadCount > 0)
                <span class="wa-bell-count-badge">{{ $unreadCount }}</span>
                @endif
            </div>
            <div class="wa-bell-header-actions">
                @if($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="wa-bell-mark-all">
                    <i class="bi bi-check2-all"></i> قراءة الكل
                </button>
                @endif
                <button type="button" @click="open = false" class="wa-bell-close-btn" aria-label="إغلاق">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        {{-- قائمة الإشعارات --}}
        <div class="wa-bell-list">
            @forelse($notifications as $notif)
            <div class="wa-bell-item {{ $notif->is_read ? '' : 'wa-bell-item-unread' }}"
                 wire:click="markAsRead({{ $notif->id }})">

                {{-- ✨ مُصحَّح: types تطابق Notification::TYPE_* constants الجديدة --}}
                <div class="wa-bell-icon wa-bell-icon-{{ $notif->type }}">
                    @switch($notif->type)
                        @case(\App\Models\Notification::TYPE_APPROVAL_REQUEST)
                        @case('approval_requested') {{-- legacy: للإشعارات القديمة في DB --}}
                            <i class="bi bi-clipboard-check-fill"></i>
                            @break
                        @case(\App\Models\Notification::TYPE_EVENT_APPROVED)
                        @case('approvals_complete') {{-- legacy --}}
                            <i class="bi bi-check-circle-fill"></i>
                            @break
                        @case(\App\Models\Notification::TYPE_EVENT_REJECTED)
                            <i class="bi bi-x-octagon-fill"></i>
                            @break
                        @case(\App\Models\Notification::TYPE_EVENT_PUBLISHED)
                            <i class="bi bi-megaphone-fill"></i>
                            @break
                        @case(\App\Models\Notification::TYPE_EVENT_CANCELLED)
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            @break
                        @default
                            <i class="bi bi-info-circle-fill"></i>
                    @endswitch
                </div>

                {{-- محتوى الإشعار --}}
                <div class="wa-bell-content">
                    <div class="wa-bell-item-title">{{ $notif->title }}</div>
                    <div class="wa-bell-item-msg">{{ $notif->message }}</div>
                    <div class="wa-bell-item-time">
                        <i class="bi bi-clock"></i>
                        {{-- ✨ nullsafe على created_at --}}
                        {{ $notif->created_at?->diffForHumans() ?? '—' }}
                    </div>
                </div>

                {{-- نقطة "غير مقروء" --}}
                @if(!$notif->is_read)
                <span class="wa-bell-unread-dot"></span>
                @endif
            </div>
            @empty
            <div class="wa-bell-empty">
                <i class="bi bi-bell-slash"></i>
                <p>لا توجد إشعارات</p>
            </div>
            @endforelse
        </div>

    </div>

</div>
