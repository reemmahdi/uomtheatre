<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventApproval;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

/**
 * ════════════════════════════════════════════════════════════════
 * NotificationService — UOMTheatre (مُحدّث)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات في هذه النسخة (إصلاحات Claude):
 *   🟡 notifyApprovalRequested: يرسل فقط للأدوار pending
 *      (سابقاً كان يرسل حتى لمن وافق سابقاً!)
 *   🟡 استخدام Notification::TYPE_* constants بدل string literals
 *   🟡 null safety على $rejecter->role
 *   🟡 توحيد أسماء الأنواع مع Notification Model
 *
 * ════════════════════════════════════════════════════════════════
 */
class NotificationService
{
    // ════════════════════════════════════════════════════════════
    // 1. طلب موافقة على فعالية
    // ════════════════════════════════════════════════════════════

    /**
     * يُرسَل لمدير المسرح ومكتب الرئيس عند إرسال فعالية للموافقة
     *
     * 🟡 مُصحَّح: يرسل فقط للأدوار التي عندها pending approval
     *    (لو رفض شخص ثم أعدنا الإرسال، الموافق سابقاً لا يستلم إشعار)
     */
    public function notifyApprovalRequested(Event $event): void
    {
        // ✨ جلب IDs الأدوار التي عندها pending فقط (مش كل الأدوار المُحدّدة)
        $pendingRoleIds = EventApproval::where('event_id', $event->id)
            ->where('status', EventApproval::STATUS_PENDING)
            ->pluck('role_id')
            ->unique()
            ->all();

        if (empty($pendingRoleIds)) {
            return;
        }

        $approvers = User::whereIn('role_id', $pendingRoleIds)
            ->where('is_active', true)
            ->get();

        foreach ($approvers as $approver) {
            Notification::create([
                'user_id'  => $approver->id,
                'title'    => 'فعالية بانتظار موافقتك',
                'message'  => "الفعالية \"{$event->title}\" بانتظار موافقتك. اذهبي إلى صفحة \"بانتظار موافقتي\" لاتخاذ القرار.",
                'type'     => Notification::TYPE_APPROVAL_REQUEST,
                'event_id' => $event->id,
                'is_read'  => false,
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════
    // 2. اكتمال الموافقات
    // ════════════════════════════════════════════════════════════

    /**
     * يُرسَل لمدير الإعلام عند موافقة الجميع على فعاليته
     */
    public function notifyApprovalsComplete(Event $event): void
    {
        if (!$event->created_by) {
            return;
        }

        Notification::create([
            'user_id'  => $event->created_by,
            'title'    => '✅ تمت الموافقة على فعاليتك',
            'message'  => "تمت الموافقة على فعالية \"{$event->title}\" من جميع الجهات المختصة. يمكنك الآن نشرها للجمهور.",
            'type'     => Notification::TYPE_EVENT_APPROVED,
            'event_id' => $event->id,
            'is_read'  => false,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 3. رفض فعالية
    // ════════════════════════════════════════════════════════════

    /**
     * يُرسَل لمدير الإعلام عند رفض فعاليته
     *
     * 🟡 مُصحَّح: nullsafe على role
     */
    public function notifyEventRejected(Event $event, User $rejecter, ?string $reason = null): void
    {
        if (!$event->created_by) {
            return;
        }

        // ✨ تأكد من تحميل العلاقة + nullsafe
        $rejecterRoleName = $rejecter->role?->display_name ?? 'الجهة المختصة';

        $reasonText = $reason ? "\n\nسبب الرفض: {$reason}" : '';

        Notification::create([
            'user_id'  => $event->created_by,
            'title'    => '⛔ تم رفض فعاليتك',
            'message'  => "تم رفض فعالية \"{$event->title}\" من قبل {$rejecterRoleName}.{$reasonText}",
            'type'     => Notification::TYPE_EVENT_REJECTED,
            'event_id' => $event->id,
            'is_read'  => false,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 4. نشر فعالية (للسوبر أدمن - مراقبة)
    // ════════════════════════════════════════════════════════════

    public function notifyEventPublished(Event $event): void
    {
        $admins = User::whereHas('role', fn($q) => $q->where('name', Role::SUPER_ADMIN))
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => '📢 فعالية جديدة منشورة',
                'message'  => "تم نشر فعالية \"{$event->title}\" للجمهور.",
                'type'     => Notification::TYPE_EVENT_PUBLISHED,
                'event_id' => $event->id,
                'is_read'  => false,
            ]);
        }
    }

    /**
     * ✨ جديد: إشعار إلغاء فعالية (للحجوزات الموجودة)
     * يُستخدم في المرحلة 3.و من الملخص
     */
    public function notifyEventCancelled(Event $event, ?string $reason = null): int
    {
        // كل المستخدمين الذين عندهم حجز نشط في هذه الفعالية
        $userIds = $event->reservations()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        if ($userIds->isEmpty()) {
            return 0;
        }

        $reasonText = $reason ? "\n\nالسبب: {$reason}" : '';
        $count = 0;

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id'  => $userId,
                'title'    => '⚠️ تم إلغاء الفعالية',
                'message'  => "نأسف لإبلاغكِ بإلغاء فعالية \"{$event->title}\".{$reasonText}",
                'type'     => Notification::TYPE_EVENT_CANCELLED,
                'event_id' => $event->id,
                'is_read'  => false,
            ]);
            $count++;
        }

        return $count;
    }

    // ════════════════════════════════════════════════════════════
    // Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * تعليم كل إشعارات مستخدم كمقروءة
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * عدّ الإشعارات غير المقروءة لمستخدم
     */
    public function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
}
