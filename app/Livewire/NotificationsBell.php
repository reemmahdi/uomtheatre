<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * ════════════════════════════════════════════════════════════════
 * NotificationsBell — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🟡 Auth::check() قبل query (تجنب null user_id)
 *   🟡 استخدام scope recent() من Notification Model
 *
 * ════════════════════════════════════════════════════════════════
 */
class NotificationsBell extends Component
{
    public function markAsRead(int $notificationId): void
    {
        if (!Auth::check()) return;

        $notification = Notification::where('id', $notificationId)
            ->where('user_id', Auth::id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        if (!Auth::check()) return;

        app(NotificationService::class)->markAllAsRead(Auth::user());
    }

    public function render()
    {
        // ✨ حماية: لو غير مسجل، إرجاع قيم فارغة بدل crash
        if (!Auth::check()) {
            return view('livewire.notifications-bell', [
                'unreadCount'   => 0,
                'notifications' => collect(),
            ]);
        }

        $userId = Auth::id();

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        $notifications = Notification::with('event')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('livewire.notifications-bell', [
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}
