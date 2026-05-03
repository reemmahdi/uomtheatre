<div>

<div class="card-custom p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted">إجمالي الموظفين: <strong>{{ $staff->count() }}</strong></span>
            <span class="text-muted me-3">| موظفي النظام الذين يدخلون لوحة التحكم</span>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-person-plus"></i> إضافة موظف جديد
        </button>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>#</th><th>الاسم</th><th>البريد</th><th>الجوال</th><th>الدور</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th></tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                @php
                    $roleColors = ['super_admin'=>'#e74c3c','event_manager'=>'#f39c12','theater_manager'=>'#2e75b6','receptionist'=>'#27ae60','university_office'=>'#8e44ad'];
                    $color = $roleColors[$member->role->name] ?? '#95a5a6';
                @endphp
                <tr>
                    <td>{{ $member->id }}</td>
                    <td><strong>{{ $member->name }}</strong></td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->phone ?? '—' }}</td>
                    <td><span class="badge-role" style="background:{{ $color }}20;color:{{ $color }};border:1px solid {{ $color }}40;">{{ $member->role->display_name }}</span></td>
                    <td>@if($member->is_active)<span class="badge bg-success"><i class="bi bi-check-circle"></i> فعال</span>@else<span class="badge bg-danger"><i class="bi bi-x-circle"></i> معطّل</span>@endif</td>
                    <td>{{ $member->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" wire:click="openEdit({{ $member->id }})" data-bs-toggle="modal" data-bs-target="#editModal" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($member->is_active)
                            <button class="btn btn-outline-danger" wire:click="requestToggleStatus({{ $member->id }})" title="تعطيل">
                                <i class="bi bi-person-x"></i>
                            </button>
                            @else
                            <button class="btn btn-outline-success" wire:click="requestToggleStatus({{ $member->id }})" title="تفعيل">
                                <i class="bi bi-person-check"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:40px;"></i><p class="mt-2">لا يوجد موظفين</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- نافذة إضافة موظف --}}
<div class="modal fade" id="createModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus"></i> إضافة موظف جديد</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @error('name')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('email')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('password')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('role_id')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3"><label class="form-label fw-bold">الاسم <span class="text-danger">*</span></label><input type="text" wire:model="name" class="form-control" placeholder="الاسم الكامل"></div>
                <div class="mb-3"><label class="form-label fw-bold">البريد <span class="text-danger">*</span></label><input type="email" wire:model="email" class="form-control" placeholder="example@uomosul.edu.iq"></div>

                {{-- حقل كلمة المرور مع Strength Meter --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        كلمة المرور <span class="text-danger">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password"
                               wire:model.live="password"
                               class="form-control"
                               id="createPassword"
                               placeholder="12 رمز على الأقل مع تنوع">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('createPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    {{-- Password Strength Meter --}}
                    <div class="password-strength-meter" x-data x-show="$wire.password && $wire.password.length > 0">
                        <div class="strength-bars">
                            <div class="strength-bar" data-level="1"></div>
                            <div class="strength-bar" data-level="2"></div>
                            <div class="strength-bar" data-level="3"></div>
                            <div class="strength-bar" data-level="4"></div>
                            <div class="strength-bar" data-level="5"></div>
                        </div>
                        <div class="strength-label"></div>
                    </div>

                    {{-- قائمة شروط كلمة المرور --}}
                    <div class="password-requirements" x-data x-show="$wire.password && $wire.password.length > 0">
                        <div class="req-item" data-req="length">
                            <i class="bi bi-circle"></i> 12 رمز على الأقل
                        </div>
                        <div class="req-item" data-req="uppercase">
                            <i class="bi bi-circle"></i> حرف كبير (A-Z)
                        </div>
                        <div class="req-item" data-req="lowercase">
                            <i class="bi bi-circle"></i> حرف صغير (a-z)
                        </div>
                        <div class="req-item" data-req="number">
                            <i class="bi bi-circle"></i> رقم (0-9)
                        </div>
                        <div class="req-item" data-req="special">
                            <i class="bi bi-circle"></i> رمز خاص (@#$%^&amp;*)
                        </div>
                    </div>
                </div>

                <div class="mb-3"><label class="form-label fw-bold">الجوال</label><input type="text" wire:model="phone" class="form-control" placeholder="07xxxxxxxxx"></div>
                <div class="mb-3"><label class="form-label fw-bold">الدور <span class="text-danger">*</span></label>
                    <select wire:model="role_id" class="form-select">
                        <option value="0">اختر الدور...</option>
                        @foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name }} — {{ $role->description }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="createStaff" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createStaff"><i class="bi bi-person-plus"></i> إضافة</span>
                    <span wire:loading wire:target="createStaff"><span class="wire-loading"></span> جاري الإضافة...</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- نافذة تعديل موظف --}}
<div class="modal fade" id="editModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> تعديل الموظف</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @error('editName')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editEmail')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editPassword')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3"><label class="form-label fw-bold">الاسم</label><input type="text" wire:model="editName" class="form-control"></div>
                <div class="mb-3"><label class="form-label fw-bold">البريد</label><input type="email" wire:model="editEmail" class="form-control"></div>
                <div class="mb-3"><label class="form-label fw-bold">الجوال</label><input type="text" wire:model="editPhone" class="form-control"></div>
                <div class="mb-3"><label class="form-label fw-bold">الدور</label>
                    <select wire:model="editRoleId" class="form-select">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name }}</option>@endforeach</select>
                </div>

                {{-- حقل تعديل كلمة المرور (اختياري) مع Strength Meter --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">كلمة مرور جديدة (اختياري)</label>
                    <div class="password-input-wrapper">
                        <input type="password"
                               wire:model.live="editPassword"
                               class="form-control"
                               id="editPassword"
                               placeholder="اتركيه فارغاً إذا ما تبين تغيير">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('editPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    {{-- Password Strength Meter --}}
                    <div class="password-strength-meter" x-data x-show="$wire.editPassword && $wire.editPassword.length > 0">
                        <div class="strength-bars">
                            <div class="strength-bar" data-level="1"></div>
                            <div class="strength-bar" data-level="2"></div>
                            <div class="strength-bar" data-level="3"></div>
                            <div class="strength-bar" data-level="4"></div>
                            <div class="strength-bar" data-level="5"></div>
                        </div>
                        <div class="strength-label"></div>
                    </div>

                    {{-- قائمة شروط كلمة المرور --}}
                    <div class="password-requirements" x-data x-show="$wire.editPassword && $wire.editPassword.length > 0">
                        <div class="req-item" data-req="length">
                            <i class="bi bi-circle"></i> 12 رمز على الأقل
                        </div>
                        <div class="req-item" data-req="uppercase">
                            <i class="bi bi-circle"></i> حرف كبير (A-Z)
                        </div>
                        <div class="req-item" data-req="lowercase">
                            <i class="bi bi-circle"></i> حرف صغير (a-z)
                        </div>
                        <div class="req-item" data-req="number">
                            <i class="bi bi-circle"></i> رقم (0-9)
                        </div>
                        <div class="req-item" data-req="special">
                            <i class="bi bi-circle"></i> رمز خاص (@#$%^&amp;*)
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="updateStaff" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateStaff">حفظ التعديلات</span>
                    <span wire:loading wire:target="updateStaff"><span class="wire-loading"></span> جاري الحفظ...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@script
<script>
    // إغلاق المودال بعد حفظ ناجح
    $wire.on('close-modal', () => {
        document.querySelectorAll('.modal').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
    });
</script>
@endscript

<script>
// ==================== Password Strength Meter Logic ====================
function checkPasswordStrength(password) {
    const checks = {
        length:    password.length >= 12,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number:    /[0-9]/.test(password),
        special:   /[^a-zA-Z0-9]/.test(password)
    };

    const score = Object.values(checks).filter(Boolean).length;
    return { checks, score };
}

function updatePasswordUI(inputEl) {
    const wrapper = inputEl.closest('.mb-3');
    if (!wrapper) return;

    const password = inputEl.value;
    const { checks, score } = checkPasswordStrength(password);

    // تحديث شروط كلمة المرور (الإطار الأخضر عند التحقق)
    const reqItems = wrapper.querySelectorAll('.req-item');
    reqItems.forEach(item => {
        const reqKey = item.dataset.req;
        const icon = item.querySelector('i');
        if (checks[reqKey]) {
            item.classList.add('req-met');
            if (icon) { icon.classList.remove('bi-circle'); icon.classList.add('bi-check-circle-fill'); }
        } else {
            item.classList.remove('req-met');
            if (icon) { icon.classList.remove('bi-check-circle-fill'); icon.classList.add('bi-circle'); }
        }
    });

    // تحديث الـ Strength Bars
    const bars = wrapper.querySelectorAll('.strength-bar');
    const label = wrapper.querySelector('.strength-label');
    const labels = ['', 'ضعيفة جداً', 'ضعيفة', 'متوسطة', 'جيدة', 'ممتازة'];
    const labelClasses = ['', 'weak-1', 'weak-2', 'medium', 'good', 'excellent'];

    bars.forEach((bar, idx) => {
        bar.className = 'strength-bar';
        if (idx < score) {
            bar.classList.add('active', `strength-${score}`);
        }
    });

    if (label) {
        label.textContent = labels[score] || '';
        label.className = 'strength-label ' + (labelClasses[score] || '');
    }
}

// الاستماع لتغيّر حقول كلمة المرور (Event Delegation)
document.addEventListener('input', function(e) {
    if (e.target && (e.target.id === 'createPassword' || e.target.id === 'editPassword')) {
        updatePasswordUI(e.target);
    }
});

// زر إظهار/إخفاء كلمة المرور
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

</div>
