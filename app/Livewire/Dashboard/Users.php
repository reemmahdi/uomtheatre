<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * Users — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 إزالة hardcoded role_id=6 → Role::USER بشكل ديناميكي
 *   🔴 authorize() في كل method (مش فقط render)
 *   🟡 nullsafe على role
 *   🟡 redirect في mount بدل render
 *
 * ════════════════════════════════════════════════════════════════
 */
#[Layout('layouts.app')]
#[Title('إدارة المستخدمين')]
class Users extends BaseComponent
{
    /**
     * ✨ helper مشترك: التحقق من super_admin قبل أي إجراء حساس
     */
    protected function authorizeSuperAdmin(): void
    {
        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'هذه الصفحة متاحة لمدير النظام فقط');
        }
    }

    /**
     * ✨ مُحسّن: redirect في mount بدل render
     */
    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    // ════════════════════════════════════════════════════════════
    // طلب تأكيد تغيير الحالة
    // ════════════════════════════════════════════════════════════
    public function requestToggleStatus(int $id): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            $this->swalError('لا يمكنك تعطيل حسابك الشخصي');
            return;
        }

        $action  = $user->is_active ? 'تعطيل' : 'تفعيل';
        $message = "هل أنت متأكد من {$action} حساب \"{$user->name}\"؟";

        $this->swalConfirm(
            message: $message,
            action:  'confirmToggleStatus',
            params:  $id,
            title:   "تأكيد {$action}"
        );
    }

    // ════════════════════════════════════════════════════════════
    // تنفيذ تغيير الحالة بعد التأكيد
    // ════════════════════════════════════════════════════════════
    #[On('confirmToggleStatus')]
    public function confirmToggleStatus($id): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check (event-based methods حساسة)

        try {
            $user = User::findOrFail($id);

            if ($user->id === Auth::id()) {
                $this->swalError('لا يمكنك تعطيل حسابك الشخصي');
                return;
            }

            $user->update(['is_active' => !$user->is_active]);

            $statusText = $user->is_active ? 'تفعيل' : 'تعطيل';
            $this->swalToast("تم {$statusText} حساب \"{$user->name}\"");
        } catch (\Exception $e) {
            $this->swalError('حدث خطأ: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // Render
    // ════════════════════════════════════════════════════════════
    public function render()
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

        // ✨ مُصحَّح: استخدام Role::USER بدل hardcoded 6
        $userRoleId = Role::where('name', Role::USER)->value('id');

        return view('livewire.dashboard.users', [
            'users' => User::with('role')
                ->where('role_id', $userRoleId)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
