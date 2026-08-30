<?php

namespace App\Livewire\Dashboard;

use App\Livewire\BaseComponent;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('إدارة الصلاحيات')]
class Permissions extends BaseComponent
{
    public array $rolePermissions = [];
    public bool $hasChanges = false;

    protected function authorizeSuperAdmin(): void
    {
        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'هذه الشاشة متاحة لمدير النظام فقط');
        }
    }

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
        $this->loadRolePermissions();
    }

    protected function loadRolePermissions(): void
    {
        $this->rolePermissions = [];

        $allPermissionIds = Permission::pluck('id')->all();

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $rolePermIds = $role->permissions->pluck('id')->all();

            $this->rolePermissions[$role->id] = [];
            foreach ($allPermissionIds as $permId) {
                $this->rolePermissions[$role->id][$permId] = in_array($permId, $rolePermIds, true);
            }
        }

        $this->hasChanges = false;
    }

    public function toggle(int $roleId, int $permissionId): void
    {
        $this->authorizeSuperAdmin();

        if (!isset($this->rolePermissions[$roleId][$permissionId])) {
            return;
        }

        $this->rolePermissions[$roleId][$permissionId] = !$this->rolePermissions[$roleId][$permissionId];
        $this->hasChanges = true;
    }

    public function selectAllForRole(int $roleId): void
    {
        $this->authorizeSuperAdmin();

        if (!isset($this->rolePermissions[$roleId])) {
            return;
        }

        foreach ($this->rolePermissions[$roleId] as $permId => $value) {
            $this->rolePermissions[$roleId][$permId] = true;
        }
        $this->hasChanges = true;

        $this->swalToast('تم تحديد كل الصلاحيات لهذا الدور');
    }

    public function deselectAllForRole(int $roleId): void
    {
        $this->authorizeSuperAdmin();

        if (!isset($this->rolePermissions[$roleId])) {
            return;
        }

        foreach ($this->rolePermissions[$roleId] as $permId => $value) {
            $this->rolePermissions[$roleId][$permId] = false;
        }
        $this->hasChanges = true;

        $this->swalToast('تم إلغاء كل الصلاحيات لهذا الدور');
    }

    public function requestSave(): void
    {
        $this->authorizeSuperAdmin();

        if (!$this->hasChanges) {
            $this->swalToast('لا توجد تغييرات للحفظ');
            return;
        }

        $this->swalConfirm(
            message: 'سيتم تحديث صلاحيات الأدوار. هل أنت متأكد؟',
            action:  'confirmSave',
            title:   'تأكيد حفظ الصلاحيات'
        );
    }

    #[On('confirmSave')]
    public function confirmSave(): void
    {
        $this->authorizeSuperAdmin();

        try {
            DB::transaction(function () {
                foreach ($this->rolePermissions as $roleId => $permissions) {
                    $activePermissionIds = collect($permissions)
                        ->filter(fn($value) => $value === true)
                        ->keys()
                        ->all();

                    $role = Role::find($roleId);
                    if ($role) {
                        $role->permissions()->sync($activePermissionIds);
                    }
                }
            });

            if (method_exists(Auth::user(), 'clearPermissionsCache')) {
                Auth::user()->clearPermissionsCache();
            }

            $this->hasChanges = false;
            $this->swalSuccess('تم حفظ الصلاحيات بنجاح');
        } catch (\Exception $e) {
            $this->swalError('فشل الحفظ: ' . $e->getMessage());
        }
    }

    public function requestReset(): void
    {
        $this->authorizeSuperAdmin();

        if (!$this->hasChanges) {
            return;
        }

        $this->swalConfirm(
            message: 'سيتم تجاهل التغييرات غير المحفوظة. هل أنت متأكد؟',
            action:  'confirmReset',
            title:   'تجاهل التغييرات'
        );
    }

    #[On('confirmReset')]
    public function confirmReset(): void
    {
        $this->authorizeSuperAdmin();
        $this->loadRolePermissions();
        $this->swalToast('تم استعادة الصلاحيات الأصلية');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $roles = Role::whereNotIn('name', [Role::SUPER_ADMIN, Role::USER])
            ->orderBy('id')
            ->get();

        $permissions = Permission::orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');

        $groupLabels = [
            'events'     => 'إدارة الفعاليات',
            'approvals'  => 'الموافقات',
            'publishing' => 'النشر والإشعارات',
            'vip'        => 'الوفود والمقاعد',
            'checkin'    => 'تسجيل الحضور',
            'admin'      => 'الإدارة',
            'general'    => 'عام',
        ];

        return view('livewire.dashboard.permissions', [
            'roles'       => $roles,
            'permissions' => $permissions,
            'groupLabels' => $groupLabels,
        ]);
    }
}
