<div>

<div class="card-custom p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted">إجمالي المستخدمين: <strong>{{ $users->count() }}</strong></span>
            <span class="text-muted me-3">| المستخدمين الذين يحجزون من التطبيق</span>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>#</th><th>الاسم</th><th>البريد الإلكتروني</th><th>الجوال</th><th>الحالة</th><th>تاريخ التسجيل</th><th>الإجراءات</th></tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? '—' }}</td>
                    <td>@if($user->is_active)<span class="badge bg-success"><i class="bi bi-check-circle"></i> فعال</span>@else<span class="badge bg-danger"><i class="bi bi-x-circle"></i> معطّل</span>@endif</td>
                    <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    <td>
                        @if($user->is_active)
                        <button class="btn btn-sm btn-outline-danger" wire:click="requestToggleStatus({{ $user->id }})" title="تعطيل">
                            <i class="bi bi-person-x"></i>
                        </button>
                        @else
                        <button class="btn btn-sm btn-outline-success" wire:click="requestToggleStatus({{ $user->id }})" title="تفعيل">
                            <i class="bi bi-person-check"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:40px;"></i><p class="mt-2">لا يوجد مستخدمين مسجلين من التطبيق بعد</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
