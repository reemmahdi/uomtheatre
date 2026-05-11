<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Role;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

/**
 * ════════════════════════════════════════════════════════════════
 * Staff — UOMTheatre (مُحدّث - إصلاحات Claude)
 * ════════════════════════════════════════════════════════════════
 *
 * ✨ التعديلات:
 *   🔴 إزالة hardcoded role_id=6 → Role::USER ديناميكياً
 *   🔴 authorize() في كل method (مش فقط render)
 *   🔴 منع إنشاء/تعديل super_admin من الـ form (security)
 *   🟡 nullsafe على role
 *   🟡 redirect في mount بدل render
 *
 * ════════════════════════════════════════════════════════════════
 */
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

    /**
     * ✨ helper: التحقق من super_admin
     */
    protected function authorizeSuperAdmin(): void
    {
        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'هذه الصفحة متاحة لمدير النظام فقط');
        }
    }

    /**
     * ✨ helper: التحقق أن الدور المختار ليس super_admin (حماية)
     */
    protected function validateRoleNotSuperAdmin(int $roleId): void
    {
        $superAdminId = Role::where('name', Role::SUPER_ADMIN)->value('id');
        if ($roleId === $superAdminId) {
            $this->addError('role_id', 'لا يمكن إنشاء حساب super_admin من هذه الشاشة');
            abort(422);
        }
    }

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    // ==================== إنشاء موظف جديد ====================
    public function createStaff(): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

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

        // ✨ منع إنشاء super_admin من هذه الشاشة (security)
        $this->validateRoleNotSuperAdmin($this->role_id);

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
    public function openEdit(int $id): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

        $user = User::findOrFail($id);

        // ✨ منع تعديل super_admin من هنا (يجب أن يكون من الـ database مباشرة)
        if ($user->isSuperAdmin()) {
            $this->swalError('حسابات super_admin لا يمكن تعديلها من هذه الشاشة');
            return;
        }

        $this->editId       = $user->id;
        $this->editName     = $user->name;
        $this->editEmail    = $user->email;
        $this->editPhone    = $user->phone ?? '';
        $this->editRoleId   = $user->role_id;
        $this->editPassword = '';
    }

    // ==================== تحديث بيانات الموظف ====================
    public function updateStaff(): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

        $rules = [
            'editName'   => 'required|string|max:255',
            'editEmail'  => 'required|email|unique:users,email,' . $this->editId,
            'editRoleId' => 'required|exists:roles,id',
        ];

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

        $this->validateRoleNotSuperAdmin($this->editRoleId);

        try {
            $user = User::findOrFail($this->editId);

            // ✨ حماية إضافية: منع ترقية حساب موجود إلى super_admin
            if ($user->isSuperAdmin()) {
                $this->swalError('حسابات super_admin لا يمكن تعديلها');
                return;
            }

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
    public function requestToggleStatus(int $id): void
    {
        $this->authorizeSuperAdmin();

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            $this->swalError('لا يمكنك تعطيل حسابك الشخصي');
            return;
        }

        $action = $user->is_active ? 'تعطيل' : 'تفعيل';
        $message = "هل أنت متأكد من {$action} حساب \"{$user->name}\"؟";

        $this->swalConfirm(
            message: $message,
            action:  'confirmToggleStatus',
            params:  $id,
            title:   "تأكيد {$action}"
        );
    }

    // ==================== تنفيذ تغيير الحالة بعد التأكيد ====================
    #[On('confirmToggleStatus')]
    public function confirmToggleStatus($id): void
    {
        $this->authorizeSuperAdmin();   // ✨ authorize check

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

    // ==================== Render ====================
    public function render()
    {
        $this->authorizeSuperAdmin();

        // ✨ مُصحَّح: ديناميكي بدل hardcoded 6
        $userRoleId        = Role::where('name', Role::USER)->value('id');
        $superAdminRoleId  = Role::where('name', Role::SUPER_ADMIN)->value('id');

        return view('livewire.dashboard.staff', [
            // الموظفون: كل من ليس "user" عادي ولا "super_admin"
            'staff' => User::with('role')
                ->whereNotIn('role_id', array_filter([$userRoleId, $superAdminRoleId]))
                ->orderByDesc('created_at')
                ->get(),
            // الأدوار المسموحة في الفورم: ليست super_admin ولا user
            'roles' => Role::whereNotIn('name', [Role::SUPER_ADMIN, Role::USER])
                ->orderBy('id')
                ->get(),
        ]);
    }
}
