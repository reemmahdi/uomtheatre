<div>

<div class="card-custom p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted">
            إجمالي الفعاليات: <strong>{{ $events->count() }}</strong>
            @if($searchTitle || $filterStatus || $filterDateFrom || $filterDateTo)
            <span class="badge bg-info ms-2"><i class="bi bi-funnel-fill"></i> فلاتر مُفعَّلة</span>
            @endif
        </span>
        @if(in_array($roleName, ['super_admin', 'theater_manager']))
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="bi bi-plus-circle"></i> إنشاء فعالية جديدة
        </button>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--  ✨ شريط البحث والفلاتر                        --}}
{{-- ══════════════════════════════════════════════ --}}
<div class="card-custom p-3 mb-4 filters-bar">
    <div class="row g-2 align-items-end">
        {{-- بحث بالعنوان --}}
        <div class="col-md-4">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-search"></i> البحث باسم الفعالية
            </label>
            <input type="text"
                   wire:model.live.debounce.400ms="searchTitle"
                   class="form-control form-control-sm"
                   placeholder="اكتب جزءاً من عنوان الفعالية...">
        </div>

        {{-- فلتر الحالة --}}
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-flag"></i> الحالة
            </label>
            <select wire:model.live="filterStatus" class="form-select form-select-sm">
                <option value="">— كل الحالات —</option>
                @foreach($allStatuses as $st)
                <option value="{{ $st->name }}">{{ $st->display_name ?? $st->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- من تاريخ --}}
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-calendar-event"></i> من تاريخ
            </label>
            <input type="date"
                   wire:model.live="filterDateFrom"
                   class="form-control form-control-sm">
        </div>

        {{-- إلى تاريخ --}}
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-calendar-check"></i> إلى تاريخ
            </label>
            <input type="date"
                   wire:model.live="filterDateTo"
                   class="form-control form-control-sm">
        </div>

        {{-- زر مسح الفلاتر --}}
        <div class="col-md-1">
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-outline-secondary w-100"
                    title="مسح كل الفلاتر">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>عنوان الفعالية </th>
                    <th>موعد الانطلاق</th>
                    <th>موعد الاختتام</th>
                    <th class="status-col">الحالة</th>
                    <th class="actions-col">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                @php
                    $statusColors = ['draft'=>'#6B7280','added'=>'#3B82F6','under_review'=>'#F59E0B','active'=>'#8B5CF6','published'=>'#10B981','closed'=>'#EF4444','cancelled'=>'#DC2626','end'=>'#9CA3AF'];
                    $statusNames = ['draft'=>'مسودة','added'=>'مضافة','under_review'=>'قيد المراجعة','active'=>'نشطة','published'=>'منشورة','closed'=>'مغلقة','cancelled'=>'ملغاة','end'=>'منتهية'];
                    $sName = $event->status->name;
                    $sColor = $statusColors[$sName] ?? '#6B7280';
                    $sLabel = $statusNames[$sName] ?? $sName;
                    $isCancelled = ($sName === 'cancelled');
                    $isPaused = $event->is_booking_paused;
                @endphp
                {{-- ✨ تمييز بصري: وردي للملغاة، أصفر فاتح للموقوفة --}}
                <tr @if($isCancelled) style="background-color: #fef2f2;" @elseif($isPaused) style="background-color: #fffbeb;" @endif>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong @if($isCancelled) style="color: #DC2626; text-decoration: line-through;" @endif>
                            @if($isCancelled) <i class="bi bi-x-octagon-fill text-danger"></i> @endif
                            @if($isPaused && !$isCancelled) <i class="bi bi-pause-circle-fill text-warning"></i> @endif
                            {{ $event->title }}
                        </strong>
                        @if($event->description)
                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($event->description, 50) }}</small>
                        @endif
                        {{-- سبب الإلغاء --}}
                        @if($isCancelled && $event->cancellation_reason)
                        <br><small class="text-danger fst-italic">
                            <i class="bi bi-info-circle"></i> سبب الإلغاء: {{ \Illuminate\Support\Str::limit($event->cancellation_reason, 60) }}
                        </small>
                        @endif
                        {{-- ✨ تنبيه الإيقاف المؤقت --}}
                        @if($isPaused && !$isCancelled)
                        <br><small class="text-warning fst-italic">
                            <i class="bi bi-pause-fill"></i> الحجز موقوف مؤقتاً
                            @if($event->paused_at)
                                — منذ {{ $event->paused_at->format('Y-m-d H:i') }}
                            @endif
                        </small>
                        @endif
                    </td>
                    <td>
                        <div class="small">
                            <i class="bi bi-calendar3 text-muted"></i> {{ $event->start_datetime->format('Y-m-d') }}
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-clock"></i> {{ $event->start_datetime->format('H:i') }}
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            <i class="bi bi-calendar3 text-muted"></i> {{ $event->end_datetime->format('Y-m-d') }}
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-clock"></i> {{ $event->end_datetime->format('H:i') }}
                        </div>
                    </td>
                    <td class="status-cell">
                        <div class="status-stack">
                            <span class="status-badge status-badge-{{ $sName }}">
                                @if($isCancelled) <i class="bi bi-x-octagon-fill"></i> @endif
                                {{ $sLabel }}
                            </span>
                            {{-- ✨ Badge للإيقاف المؤقت --}}
                            @if($isPaused && !$isCancelled)
                            <span class="status-badge status-badge-paused">
                                <i class="bi bi-pause-circle-fill"></i> الحجز موقوف
                            </span>
                            @endif
                        </div>
                    </td>
                    {{-- ══════════════════════════════════════════════ --}}
                    {{--  ✨ خلية الإجراءات المنظّمة (مجموعتين)            --}}
                    {{-- ══════════════════════════════════════════════ --}}
                    <td class="actions-cell">
                        {{-- مجموعة 1: العرض + إجراءات الحالة --}}
                        <div class="actions-group">
                            {{-- 1. عرض التفاصيل (دائماً) --}}
                            <button class="btn-action btn-action-view"
                                    wire:click="viewEvent({{ $event->id }})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewEventModal"
                                    title="عرض التفاصيل">
                                <i class="bi bi-eye"></i>
                            </button>

                            {{-- 2. مدير المسرح: تعديل + إرسال (للمسودات) --}}
                            @if(in_array($roleName, ['super_admin', 'theater_manager']))
                                @if($sName === 'draft')
                                <button class="btn-action btn-action-edit"
                                        wire:click="openEdit({{ $event->id }})"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editEventModal"
                                        title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-action btn-action-send"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'added')"
                                        title="إرسال للمراجعة">
                                    <i class="bi bi-send"></i>
                                </button>
                                @endif
                            @endif

                            {{-- 3. مدير الإعلام: مراجعة / قبول / نشر / إغلاق / وفود --}}
                            @if(in_array($roleName, ['super_admin', 'event_manager']))
                                @if($sName === 'added')
                                <button class="btn-action btn-action-review"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'under_review')"
                                        title="بدء المراجعة">
                                    <i class="bi bi-search"></i>
                                </button>
                                @endif

                                @if($sName === 'under_review')
                                <button class="btn-action btn-action-approve"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'active')"
                                        title="قبول الفعالية">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                @endif

                                @if(in_array($sName, ['active', 'under_review', 'published']))
                                <a href="{{ route('dashboard.vip-booking', $event->id) }}"
                                   class="btn-action btn-action-vip"
                                   title="إدارة مقاعد الوفود">
                                    <i class="bi bi-star-fill"></i>
                                </a>
                                @endif

                                @if($sName === 'active')
                                <button class="btn-action btn-action-publish"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'published')"
                                        title="نشر الفعالية">
                                    <i class="bi bi-megaphone-fill"></i>
                                </button>
                                @endif

                                @if($sName === 'published')
                                <button class="btn-action btn-action-close"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'closed')"
                                        title="إغلاق الفعالية">
                                    <i class="bi bi-lock-fill"></i>
                                </button>

                                {{-- ✨ زر الإيقاف/الاستئناف --}}
                                @if(!$isPaused)
                                <button class="btn-action btn-action-pause"
                                        wire:click="requestPauseBooking({{ $event->id }})"
                                        title="إيقاف الحجز مؤقتاً">
                                    <i class="bi bi-pause-circle-fill"></i>
                                </button>
                                @else
                                <button class="btn-action btn-action-resume"
                                        wire:click="requestResumeBooking({{ $event->id }})"
                                        title="استئناف الحجز">
                                    <i class="bi bi-play-circle-fill"></i>
                                </button>
                                @endif
                                @endif
                            @endif

                            {{-- زر الإلغاء (داخل نفس المجموعة لتدفق موحّد) --}}
                            @if(!in_array($sName, ['cancelled', 'end', 'closed']))
                            <button class="btn-action btn-action-cancel"
                                    wire:click="openCancelModal({{ $event->id }})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelEventModal"
                                    title="إلغاء الفعالية">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x" style="font-size:40px;color:#c39bd3;"></i>
                        @if($searchTitle || $filterStatus || $filterDateFrom || $filterDateTo)
                        <p class="mt-2">لا توجد فعاليات مطابقة لمعايير البحث</p>
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-counterclockwise"></i> مسح الفلاتر
                        </button>
                        @else
                        <p class="mt-2">لا توجد فعاليات</p>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


{{-- نافذة عرض التفاصيل --}}
<div class="modal fade" id="viewEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye"></i> تفاصيل الفعالية</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @if(!empty($showEvent))

                {{-- تنبيه الإلغاء --}}
                @if(($showEvent['status_name'] ?? '') === 'cancelled')
                <div class="alert alert-danger border-danger mb-3">
                    <h6 class="alert-heading mb-2">
                        <i class="bi bi-x-octagon-fill"></i> فعالية ملغاة
                    </h6>
                    @if(!empty($showEvent['cancellation_reason']))
                    <p class="mb-1"><strong>سبب الإلغاء:</strong></p>
                    <p class="mb-2 fst-italic">{{ $showEvent['cancellation_reason'] }}</p>
                    @endif
                    @if(!empty($showEvent['cancelled_at']))
                    <small class="text-muted"><i class="bi bi-clock-history"></i> تاريخ الإلغاء: {{ $showEvent['cancelled_at'] }}</small>
                    @endif
                </div>
                @endif

                {{-- ✨ تنبيه الإيقاف المؤقت --}}
                @if(!empty($showEvent['is_booking_paused']))
                <div class="alert alert-warning border-warning mb-3">
                    <h6 class="alert-heading mb-2">
                        <i class="bi bi-pause-circle-fill"></i>الحجز غير متاح حاليا 
                    </h6>
                    <p class="mb-2 small">الحجوزات الجديدة موقوفة، لكن الحجوزات السابقة محفوظة.</p>
                    @if(!empty($showEvent['paused_at']))
                    <small class="text-muted"><i class="bi bi-clock-history"></i> تاريخ الإيقاف: {{ $showEvent['paused_at'] }}</small>
                    @endif
                </div>
                @endif

                <div class="mb-3 p-3 rounded" style="background: linear-gradient(135deg, #fdf2f8, #f5f0ff);">
                    <h5 style="color: #7b2d8e; font-weight: 700;">{{ $showEvent['title'] }}</h5>
                </div>
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:150px;"><i class="bi bi-card-text"></i> الوصف</td><td>{{ $showEvent['description'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-calendar-event"></i> البدء</td><td>{{ $showEvent['start_datetime'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-calendar-check"></i> الانتهاء</td><td>{{ $showEvent['end_datetime'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-hourglass-split"></i> المدة</td><td>{{ $showEvent['duration'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-flag"></i> الحالة</td><td><span class="badge bg-primary">{{ $showEvent['status'] }}</span></td></tr>
                    <tr><td class="text-muted"><i class="bi bi-person"></i> أنشأها</td><td>{{ $showEvent['created_by'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-clock-history"></i> تاريخ الإنشاء</td><td>{{ $showEvent['created_at'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-megaphone"></i> تاريخ النشر</td><td>{{ $showEvent['published_at'] }}</td></tr>
                </table>
                @endif
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button></div>
        </div>
    </div>
</div>

{{-- نافذة إنشاء فعالية --}}
<div class="modal fade" id="createEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle"></i> إنشاء فعالية جديدة</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @error('title')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('start_date')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('start_time')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('end_date')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('end_time')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label fw-bold">عنوان الفعالية <span class="text-danger">*</span></label>
                    <input type="text" wire:model="title" class="form-control" placeholder="مثال: حفل تخرج كلية الهندسة">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">الوصف</label>
                    <textarea wire:model="description" class="form-control" rows="3" placeholder="وصف مختصر..."></textarea>
                </div>

                <div class="mb-3 p-3 rounded" style="background: #f0f9ff; border-right: 4px solid #0369A1;">
                    <h6 class="mb-3" style="color: #0369A1;">
                        <i class="bi bi-calendar-event"></i> موعد البدء
                    </h6>
                    <div class="row">
                        <div class="col-md-7 mb-2">
                            <label class="form-label"><i class="bi bi-calendar3"></i> التاريخ <span class="text-danger">*</span></label>
                            <input type="date" wire:model="start_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label class="form-label"><i class="bi bi-clock"></i> الوقت <span class="text-danger">*</span></label>
                            <input type="time" wire:model="start_time" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded" style="background: #f0fdf4; border-right: 4px solid #16a34a;">
                    <h6 class="mb-3" style="color: #16a34a;">
                        <i class="bi bi-calendar-check"></i> موعد الانتهاء
                    </h6>
                    <div class="row">
                        <div class="col-md-7 mb-2">
                            <label class="form-label"><i class="bi bi-calendar3"></i> التاريخ <span class="text-danger">*</span></label>
                            <input type="date" wire:model="end_date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label class="form-label"><i class="bi bi-clock"></i> الوقت <span class="text-danger">*</span></label>
                            <input type="time" wire:model="end_time" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="createEvent" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createEvent"><i class="bi bi-plus-circle"></i> إنشاء</span>
                    <span wire:loading wire:target="createEvent">جاري الإنشاء...</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- نافذة تعديل فعالية --}}
<div class="modal fade" id="editEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> تعديل الفعالية</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @error('editTitle')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editStartDate')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editStartTime')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editEndDate')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editEndTime')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3"><label class="form-label fw-bold">العنوان</label><input type="text" wire:model="editTitle" class="form-control"></div>
                <div class="mb-3"><label class="form-label fw-bold">الوصف</label><textarea wire:model="editDescription" class="form-control" rows="3"></textarea></div>

                <div class="mb-3 p-3 rounded" style="background: #f0f9ff; border-right: 4px solid #0369A1;">
                    <h6 class="mb-3" style="color: #0369A1;"><i class="bi bi-calendar-event"></i> موعد البدء</h6>
                    <div class="row">
                        <div class="col-md-7 mb-2">
                            <label class="form-label"><i class="bi bi-calendar3"></i> التاريخ</label>
                            <input type="date" wire:model="editStartDate" class="form-control">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label class="form-label"><i class="bi bi-clock"></i> الوقت</label>
                            <input type="time" wire:model="editStartTime" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded" style="background: #f0fdf4; border-right: 4px solid #16a34a;">
                    <h6 class="mb-3" style="color: #16a34a;"><i class="bi bi-calendar-check"></i> موعد الانتهاء</h6>
                    <div class="row">
                        <div class="col-md-7 mb-2">
                            <label class="form-label"><i class="bi bi-calendar3"></i> التاريخ</label>
                            <input type="date" wire:model="editEndDate" class="form-control">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label class="form-label"><i class="bi bi-clock"></i> الوقت</label>
                            <input type="time" wire:model="editEndTime" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button wire:click="updateEvent" class="btn btn-primary" wire:loading.attr="disabled">حفظ التعديلات</button>
            </div>
        </div>
    </div>
</div>

{{-- نافذة إلغاء الفعالية مع السبب --}}
<div class="modal fade" id="cancelEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border-bottom: 2px solid #DC2626;">
                <h5 class="modal-title" style="color: #DC2626;">
                    <i class="bi bi-exclamation-triangle-fill"></i> تأكيد إلغاء الفعالية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($isCancelingPublished)
                <div class="alert alert-danger mb-3">
                    <h6 class="alert-heading">
                        <i class="bi bi-exclamation-octagon-fill"></i> تحذير هام!
                    </h6>
                    <hr>
                    <p class="mb-2">
                        <strong>الفعالية "{{ $cancelEventTitle }}" منشورة حالياً</strong>
                    </p>
                    <ul class="mb-0 small">
                        <li>سيتم <strong>حذف الفعالية من التطبيق</strong> فوراً</li>
                        <li>سيتم <strong>إرسال إشعار</strong> لكل الحاجزين والوفود بالإلغاء</li>
                        @if($cancelReservationsCount > 0)
                        <li>يوجد حالياً <strong class="text-danger">{{ $cancelReservationsCount }} حجز</strong> سيتم إبلاغه</li>
                        @endif
                    </ul>
                </div>
                @else
                <p>
                    هل أنت متأكد من إلغاء الفعالية <strong>"{{ $cancelEventTitle }}"</strong>؟
                </p>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="bi bi-chat-text"></i> سبب الإلغاء
                        <small class="text-muted">(اختياري)</small>
                    </label>
                    <textarea wire:model="cancelReason"
                              class="form-control"
                              rows="3"
                              maxlength="500"
                              placeholder="اكتب سبب الإلغاء (سيُعرض في تفاصيل الفعالية ويُرسل في الإشعارات)"></textarea>
                    <small class="text-muted">الحد الأقصى: 500 حرف</small>
                    @error('cancelReason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> تراجع
                </button>
                <button wire:click="confirmCancelEvent"
                        class="btn btn-danger"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmCancelEvent">
                        <i class="bi bi-x-octagon"></i> تأكيد الإلغاء
                    </span>
                    <span wire:loading wire:target="confirmCancelEvent">
                        <span class="wire-loading"></span> جاري الإلغاء...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('close-modal', () => {
        document.querySelectorAll('.modal').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
    });
</script>
@endscript

</div>
