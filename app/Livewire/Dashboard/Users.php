<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
#[Title('إدارة المستخدمين')]
class Users extends BaseComponent
{
    // ==================== طلب تأكيد تغيير الحالة ====================
    public function requestToggleStatus(int $id)
    {
        $user = User::findOrFail($id);

        // حماية من تعطيل الحساب الشخصي
        if ($user->id == Auth::id()) {
            $this->swalError('لا يمكنك تعطيل حسابك الشخصي');
            return;
        }

        $action = $user->is_active ? 'تعطيل' : 'تفعيل';
        $message = "هل أنت متأكد من {$action} حساب \"{$user->name}\"؟";

        $this->swalConfirm(
            message: $message,
            action: 'confirmToggleStatus',
            params: $id,
            title: "تأكيد {$action}"
        );
    }

    // ==================== تنفيذ تغيير الحالة بعد التأكيد ====================
    #[On('confirmToggleStatus')]
    public function confirmToggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id == Auth::id()) {
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

    // ==================== Render ====================
    public function render()
    {
        if (Auth::user()->role->name !== 'super_admin') {
            return redirect()->route('dashboard');
        }

        return view('livewire.dashboard.users', [
            'users' => User::with('role')
                ->where('role_id', 6)
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }
}
