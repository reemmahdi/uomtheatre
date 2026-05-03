<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\User;
use App\Models\Role;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
#[Title('إدارة الموظفين')]
class Staff extends BaseComponent
{
    // ==================== Properties ====================
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $phone = '';
    public int $role_id = 0;

    public int $editId = 0;
    public string $editName = '';
    public string $editEmail = '';
    public string $editPassword = '';
    public string $editPhone = '';
    public int $editRoleId = 0;

    // ==================== إنشاء موظف جديد ====================
    public function createStaff()
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'string', new StrongPassword()],
            'phone'    => 'nullable|string',
            'role_id'  => 'required|exists:roles,id',
        ], [
            'name.required'     => 'الاسم مطلوب',
            'email.required'    => 'البريد مطلوب',
            'email.email'       => 'صيغة البريد غير صحيحة',
            'email.unique'      => 'البريد مستخدم مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'role_id.required'  => 'يجب اختيار الدور',
            'role_id.exists'    => 'الدور المحدد غير صالح',
        ]);

        try {
            User::create([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password,
                'phone'    => $this->phone,
                'role_id'  => $this->role_id,
            ]);

            $this->swalSuccess('تم إنشاء الموظف "' . $this->name . '" بنجاح');

            $this->reset(['name', 'email', 'password', 'phone', 'role_id']);
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل إنشاء الموظف: ' . $e->getMessage());
        }
    }

    // ==================== فتح نافذة التعديل ====================
    public function openEdit(int $id)
    {
        $user = User::findOrFail($id);
        $this->editId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPhone = $user->phone ?? '';
        $this->editRoleId = $user->role_id;
        $this->editPassword = '';
    }

    // ==================== تحديث بيانات الموظف ====================
    public function updateStaff()
    {
        // قواعد التحقق الأساسية
        $rules = [
            'editName'   => 'required|string|max:255',
            'editEmail'  => 'required|email|unique:users,email,' . $this->editId,
            'editRoleId' => 'required|exists:roles,id',
        ];

        // إضافة قاعدة كلمة المرور القوية فقط إذا أدخلها المستخدم
        if (!empty($this->editPassword)) {
            $rules['editPassword'] = ['string', new StrongPassword()];
        }

        $this->validate($rules, [
            'editName.required'   => 'الاسم مطلوب',
            'editEmail.required'  => 'البريد مطلوب',
            'editEmail.email'     => 'صيغة البريد غير صحيحة',
            'editEmail.unique'    => 'البريد مستخدم مسبقاً',
            'editRoleId.required' => 'يجب اختيار الدور',
        ]);

        try {
            $user = User::findOrFail($this->editId);

            $data = [
                'name'    => $this->editName,
                'email'   => $this->editEmail,
                'phone'   => $this->editPhone,
                'role_id' => $this->editRoleId,
            ];

            if (!empty($this->editPassword)) {
                $data['password'] = $this->editPassword;
            }

            $user->update($data);

            $this->swalSuccess('تم تعديل بيانات "' . $user->name . '" بنجاح');

            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            $this->swalError('فشل تعديل البيانات: ' . $e->getMessage());
        }
    }

    // ==================== طلب تأكيد تغيير الحالة ====================
    public function requestToggleStatus(int $id)
    {
        $user = User::findOrFail($id);

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

        return view('livewire.dashboard.staff', [
            'staff' => User::with('role')
                ->where('role_id', '!=', 6)
                ->orderBy('created_at', 'desc')
                ->get(),
            'roles' => Role::where('id', '!=', 6)->get(),
        ]);
    }
}
