<div>

{{-- ✨ شريط الإحصائيات والإجراءات --}}
<div class="card-custom p-3 mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-6">
            <h6 class="mb-1" style="color: var(--primary);">
                <i class="bi bi-shield-lock-fill" style="color: #8a6d1a;"></i>
                إدارة الصلاحيات
            </h6>
            <small class="text-muted">
                تخصيص صلاحيات كل دور — التغييرات لا تؤثر على مدير النظام (له كل الصلاحيات تلقائياً)
            </small>
        </div>
        <div class="col-md-6 text-end">
            @if($hasChanges)
                <span class="badge me-2" style="background: #fef3c7; color: #92400e; padding: 8px 14px; font-size: 13px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    تغييرات غير محفوظة
                </span>
                <button wire:click="requestReset" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    تراجع
                </button>
            @endif
            <button wire:click="requestSave"
                    class="btn btn-sm"
                    style="background: linear-gradient(135deg, #15803D, #166534); color: #fff; font-weight: 700; padding: 8px 18px;"
                    @if(!$hasChanges) disabled @endif>
                <i class="bi bi-save-fill"></i>
                حفظ التغييرات
            </button>
        </div>
    </div>
</div>

{{-- ✨ تحذير قبل الحفظ --}}
@if($hasChanges)
<div class="alert mb-3" style="background: #FEF3C7; border-color: #F59E0B; color: #92400E;">
    <i class="bi bi-info-circle-fill"></i>
    <strong>ملاحظة:</strong>
    التغييرات لن تُطبَّق حتى تضغطي زر <strong>"حفظ التغييرات"</strong> في الأعلى.
</div>
@endif

{{-- ✨ شريط أدوات التحديد السريع --}}
<div class="card-custom p-3 mb-3">
    <small class="text-muted d-block mb-2">
        <i class="bi bi-lightning-charge-fill" style="color: #f59e0b;"></i>
        إجراءات سريعة لكل دور:
    </small>
    <div class="row g-2">
        @foreach($roles as $role)
        <div class="col-md-3">
            <div class="border rounded p-2" style="background: #f8fafc;">
                <small class="fw-bold d-block mb-1" style="color: #0C4A6E;">
                    {{ $role->display_name }}
                </small>
                <button wire:click="selectAllForRole({{ $role->id }})"
                        class="btn btn-sm me-1"
                        style="background: #DBEAFE; color: #0C4A6E; font-size: 11px; padding: 4px 8px;">
                    <i class="bi bi-check-all"></i> الكل
                </button>
                <button wire:click="deselectAllForRole({{ $role->id }})"
                        class="btn btn-sm"
                        style="background: #FEE2E2; color: #B91C1C; font-size: 11px; padding: 4px 8px;">
                    <i class="bi bi-x"></i> لا شيء
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ✨ الجدول الرئيسي للصلاحيات --}}
<div class="card-custom p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size: 14px;">
            <thead style="background: linear-gradient(135deg, #0C4A6E, #075985); color: #fff;">
                <tr>
                    <th style="min-width: 280px; padding: 14px;">الصلاحية</th>
                    @foreach($roles as $role)
                    <th style="width: 140px; text-align: center; padding: 14px;">
                        <div style="font-weight: 700;">{{ $role->display_name }}</div>
                        <small style="font-weight: normal; opacity: 0.85; font-size: 11px;">
                            {{ $role->name }}
                        </small>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $group => $perms)
                {{-- صف العنوان لكل مجموعة --}}
                <tr style="background: #F0F9FF;">
                    <td colspan="{{ $roles->count() + 1 }}" style="padding: 12px 16px; font-weight: 700; color: #0C4A6E;">
                        {{ $groupLabels[$group] ?? '📌 ' . $group }}
                    </td>
                </tr>

                {{-- صفوف الصلاحيات --}}
                @foreach($perms as $permission)
                <tr>
                    <td style="padding: 12px 16px;">
                        <div>
                            <strong style="color: #0C4A6E;">{{ $permission->display_name }}</strong>
                            <small class="text-muted d-block" style="font-size: 12px; margin-top: 2px;">
                                <code style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 11px;">{{ $permission->name }}</code>
                            </small>
                            @if($permission->description)
                            <small class="text-muted d-block" style="font-size: 12px; margin-top: 4px;">
                                {{ $permission->description }}
                            </small>
                            @endif
                        </div>
                    </td>
                    @foreach($roles as $role)
                    <td style="text-align: center; padding: 12px;">
                        <label class="permission-checkbox-wrapper" style="cursor: pointer; display: inline-block;">
                            <input type="checkbox"
                                   wire:click="toggle({{ $role->id }}, {{ $permission->id }})"
                                   @if($rolePermissions[$role->id][$permission->id] ?? false) checked @endif
                                   class="permission-checkbox">
                            <span class="permission-checkmark"></span>
                        </label>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ✨ تذييل المعلومات --}}
<div class="mt-3 text-center">
    <small class="text-muted">
        <i class="bi bi-info-circle"></i>
        إجمالي الصلاحيات: <strong>{{ $permissions->flatten()->count() }}</strong>
        | عدد الأدوار: <strong>{{ $roles->count() }}</strong>
    </small>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- CSS مخصص للـ checkboxes --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<style>
    /* تنسيق checkbox مخصص جميل */
    .permission-checkbox-wrapper {
        position: relative;
        width: 26px;
        height: 26px;
        margin: 0;
    }

    .permission-checkbox {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        width: 26px;
        height: 26px;
        z-index: 2;
    }

    .permission-checkmark {
        position: absolute;
        top: 0;
        left: 0;
        width: 26px;
        height: 26px;
        background-color: #fff;
        border: 2px solid #CBD5E1;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .permission-checkbox-wrapper:hover .permission-checkmark {
        border-color: #0C4A6E;
        background-color: #F0F9FF;
    }

    .permission-checkbox:checked ~ .permission-checkmark {
        background: linear-gradient(135deg, #0C4A6E, #075985);
        border-color: #0C4A6E;
    }

    .permission-checkmark:after {
        content: "";
        position: absolute;
        display: none;
        left: 8px;
        top: 3px;
        width: 6px;
        height: 12px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }

    .permission-checkbox:checked ~ .permission-checkmark:after {
        display: block;
    }

    /* تأثير عند الضغط */
    .permission-checkbox:active ~ .permission-checkmark {
        transform: scale(0.92);
    }

    /* جدول جميل */
    table tbody tr:hover {
        background-color: #F0F9FF !important;
    }

    /* صفوف العناوين لا تتغير عند hover */
    table tbody tr[style*="F0F9FF"]:hover {
        background-color: #E0F2FE !important;
    }
</style>

</div>
