<div>

<div class="card-custom p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted">
            إجمالي الفعاليات: <strong>{{ $events->total() }}</strong>
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
        <div class="col-md-4 position-relative">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-search"></i> البحث باسم الفعالية
            </label>
            <input type="text"
                   wire:model.live.debounce.250ms="searchTitle"
                   wire:focus="$set('showSuggestions', true)"
                   class="form-control form-control-sm autocomplete-input"
                   placeholder="ابدأ بكتابة اسم الفعالية..."
                   autocomplete="off">

            {{-- ✨ قائمة الاقتراحات (Autocomplete) --}}
            @if($showSuggestions && count($suggestions) > 0)
            <div class="autocomplete-dropdown" wire:click.outside="hideSuggestions">
                @foreach($suggestions as $suggestion)
                <button type="button"
                        class="autocomplete-item"
                        wire:click="selectSuggestion(@js($suggestion))">
                    <i class="bi bi-search text-muted"></i>
                    <span>{!! str_ireplace($searchTitle, '<strong>'.e($searchTitle).'</strong>', e($suggestion)) !!}</span>
                </button>
                @endforeach
            </div>
            @elseif($showSuggestions && !empty($searchTitle) && count($suggestions) === 0)
            <div class="autocomplete-dropdown">
                <div class="autocomplete-empty">
                    <i class="bi bi-info-circle"></i> لا توجد فعاليات بهذا الاسم
                </div>
            </div>
            @endif
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
                    <td>{{ $events->firstItem() + $loop->index }}</td>
                    <td>
                        <strong @if($isCancelled) style="color: #64748b;" @endif>
                            @if($isCancelled) <i class="bi bi-x-octagon-fill text-danger"></i> @endif
                            @if($isPaused && !$isCancelled) <i class="bi bi-pause-circle-fill text-warning"></i> @endif
                            {{ $event->title }}
                        </strong>
                        @if($event->description)
                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($event->description, 50) }}</small>
                        @endif
                        {{-- سبب الإلغاء --}}
                        @if($isCancelled && $event->cancellation_reason)
                        <br><small class="text-muted fst-italic">
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

    {{-- ✨ روابط التصفّح (Pagination) --}}
    @if($events->hasPages())
    <div class="pagination-wrapper mt-3">
        <div class="pagination-links-only d-flex justify-content-center">
            {{ $events->onEachSide(1)->links() }}
        </div>
    </div>
    @endif
</div>


{{-- نافذة عرض التفاصيل --}}
<div class="modal fade" id="viewEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye"></i> تفاصيل الفعالية</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @if(!empty($showEvent))

                {{-- ✨ عنوان الفعالية أولاً (دائماً) --}}
                <div class="mb-3 p-3 rounded" style="background: linear-gradient(135deg, #fdf2f8, #f5f0ff);">
                    <h5 style="color: #7b2d8e; font-weight: 700; margin: 0;">{{ $showEvent['title'] }}</h5>
                </div>

                {{-- تنبيه الإلغاء (بعد العنوان) --}}
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

                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:150px;"><i class="bi bi-card-text"></i> الوصف</td><td>{{ $showEvent['description'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-calendar-event"></i> الانطلاق</td><td>{{ $showEvent['start_datetime'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-calendar-check"></i> الاختتام</td><td>{{ $showEvent['end_datetime'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-hourglass-split"></i> المدة</td><td>{{ $showEvent['duration'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-flag"></i> الحالة</td><td><span class="badge bg-primary">{{ $showEvent['status'] }}</span></td></tr>
                    <tr><td class="text-muted"><i class="bi bi-person"></i> أنشأها</td><td>{{ $showEvent['created_by'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-clock-history"></i> تاريخ الإنشاء</td><td>{{ $showEvent['created_at'] }}</td></tr>
                    <tr><td class="text-muted"><i class="bi bi-megaphone"></i> تاريخ النشر</td><td>{{ $showEvent['published_at'] }}</td></tr>
                </table>
                @endif
            </div>
            <div class="modal-footer modal-footer-uniform">
                <button type="button" class="btn btn-secondary modal-btn-cancel" data-bs-dismiss="modal">إغلاق</button>
            </div>
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
                @error('description')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('start_date')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('start_time')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('end_date')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('end_time')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label fw-bold">عنوان الفعالية <span class="text-danger">*</span></label>
                    <input type="text" wire:model="title" class="form-control" placeholder="مثال: حفل تخرج كلية الهندسة">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                        <span>وصف الفعالية <small class="text-muted fw-normal">(اختياري)</small></span>
                        <small class="text-muted fw-normal">{{ strlen($description) }} / 250 حرف</small>
                    </label>
                    <textarea wire:model.live="description"
                              class="form-control"
                              rows="4"
                              maxlength="250"
                              placeholder="اكتب وصفاً مختصراً للفعالية (اختياري - حد أقصى 250 حرف)..."></textarea>
                </div>

                <div class="mb-3 p-3 rounded date-picker-section" style="background: #e0f2fe; border-right: 5px solid #0C4A6E;">
                    <h6 class="mb-3 fw-bold" style="color: #0C4A6E;">
                        <i class="bi bi-calendar-event"></i> موعد الانطلاق
                    </h6>
                    <div class="row">
                        <div class="col-md-7 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #0C4A6E;"><i class="bi bi-calendar3"></i> التاريخ <span class="text-danger">*</span></label>
                            <input type="text" id="start_date_input"
                                   class="form-control flatpickr-date"
                                   placeholder="اختر التاريخ"
                                   value="{{ $start_date }}">
                        </div>
                        <div class="col-md-5 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #0C4A6E;"><i class="bi bi-clock"></i> الوقت <span class="text-danger">*</span></label>
                            <input type="text" id="start_time_input"
                                   class="form-control flatpickr-time"
                                   placeholder="اختر الوقت"
                                   value="{{ $start_time }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded date-picker-section" style="background: #dcfce7; border-right: 5px solid #15803D;">
                    <h6 class="mb-3 fw-bold" style="color: #15803D;">
                        <i class="bi bi-calendar-check"></i> موعد الاختتام
                    </h6>
                    <div class="row">
                        <div class="col-md-7 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #15803D;"><i class="bi bi-calendar3"></i> التاريخ <span class="text-danger">*</span></label>
                            <input type="text" id="end_date_input"
                                   class="form-control flatpickr-date"
                                   placeholder="اختر التاريخ"
                                   value="{{ $end_date }}">
                        </div>
                        <div class="col-md-5 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #15803D;"><i class="bi bi-clock"></i> الوقت <span class="text-danger">*</span></label>
                            <input type="text" id="end_time_input"
                                   class="form-control flatpickr-time"
                                   placeholder="اختر الوقت"
                                   value="{{ $end_time }}">
                        </div>
                    </div>
                </div>
            </div>
            {{-- ✨ ترتيب موحّد: الإنشاء أولاً (يمين)، الإلغاء ثانياً (يسار) - بالوسط --}}
            <div class="modal-footer modal-footer-uniform">
                <button wire:click="createEvent" class="btn btn-primary modal-btn-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createEvent"><i class="bi bi-plus-circle"></i> إنشاء</span>
                    <span wire:loading wire:target="createEvent">جاري الإنشاء...</span>
                </button>
                <button type="button" class="btn btn-secondary modal-btn-cancel" data-bs-dismiss="modal">إلغاء</button>
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
                @error('editDescription')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editStartDate')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editStartTime')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editEndDate')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
                @error('editEndTime')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror

                <div class="mb-3"><label class="form-label fw-bold">العنوان</label><input type="text" wire:model="editTitle" class="form-control"></div>

                <div class="mb-3">
                    <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                        <span>وصف الفعالية <small class="text-muted fw-normal">(اختياري)</small></span>
                        <small class="text-muted fw-normal">{{ strlen($editDescription) }} / 250 حرف</small>
                    </label>
                    <textarea wire:model.live="editDescription"
                              class="form-control"
                              rows="4"
                              maxlength="250"
                              placeholder="اكتب وصفاً مختصراً للفعالية (اختياري - حد أقصى 250 حرف)..."></textarea>
                </div>

                <div class="mb-3 p-3 rounded date-picker-section" style="background: #e0f2fe; border-right: 5px solid #0C4A6E;">
                    <h6 class="mb-3 fw-bold" style="color: #0C4A6E;"><i class="bi bi-calendar-event"></i> موعد الانطلاق</h6>
                    <div class="row">
                        <div class="col-md-7 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #0C4A6E;"><i class="bi bi-calendar3"></i> التاريخ</label>
                            <input type="text" id="edit_start_date_input" class="form-control flatpickr-date" placeholder="اختر التاريخ" value="{{ $editStartDate }}">
                        </div>
                        <div class="col-md-5 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #0C4A6E;"><i class="bi bi-clock"></i> الوقت</label>
                            <input type="text" id="edit_start_time_input" class="form-control flatpickr-time" placeholder="اختر الوقت" value="{{ $editStartTime }}">
                        </div>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded date-picker-section" style="background: #dcfce7; border-right: 5px solid #15803D;">
                    <h6 class="mb-3 fw-bold" style="color: #15803D;"><i class="bi bi-calendar-check"></i> موعد الاختتام</h6>
                    <div class="row">
                        <div class="col-md-7 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #15803D;"><i class="bi bi-calendar3"></i> التاريخ</label>
                            <input type="text" id="edit_end_date_input" class="form-control flatpickr-date" placeholder="اختر التاريخ" value="{{ $editEndDate }}">
                        </div>
                        <div class="col-md-5 mb-2" wire:ignore>
                            <label class="form-label fw-bold" style="color: #15803D;"><i class="bi bi-clock"></i> الوقت</label>
                            <input type="text" id="edit_end_time_input" class="form-control flatpickr-time" placeholder="اختر الوقت" value="{{ $editEndTime }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-uniform">
                <button wire:click="updateEvent" class="btn btn-primary modal-btn-action" wire:loading.attr="disabled">
                    <i class="bi bi-check-lg"></i> حفظ التعديلات
                </button>
                <button type="button" class="btn btn-secondary modal-btn-cancel" data-bs-dismiss="modal">إلغاء</button>
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
                              class="form-control cancel-reason-textarea"
                              rows="3"
                              maxlength="500"
                              placeholder="اكتب سبب الإلغاء (سيُعرض في تفاصيل الفعالية ويُرسل في الإشعارات)"></textarea>
                    <small class="text-muted">الحد الأقصى: 500 حرف</small>
                    @error('cancelReason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer modal-footer-uniform">
                <button wire:click="confirmCancelEvent"
                        class="btn btn-danger modal-btn-action"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmCancelEvent">
                        <i class="bi bi-x-octagon"></i> تأكيد الإلغاء
                    </span>
                    <span wire:loading wire:target="confirmCancelEvent">
                        <span class="wire-loading"></span> جاري الإلغاء...
                    </span>
                </button>
                <button type="button" class="btn btn-secondary modal-btn-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> تراجع
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

    // ═══════════════════════════════════════════════════
    // ✨ تهيئة Flatpickr - بسيطة وموثوقة
    // ═══════════════════════════════════════════════════
    let flatpickrInstances = {};

    function destroyFlatpickr() {
        Object.keys(flatpickrInstances).forEach(key => {
            try {
                flatpickrInstances[key].destroy();
            } catch (e) {}
        });
        flatpickrInstances = {};
    }

    function initFlatpickr() {
        if (typeof flatpickr === 'undefined') return;

        const arabicLocale = (flatpickr.l10ns && flatpickr.l10ns.ar) ? flatpickr.l10ns.ar : 'default';

        // قائمة الحقول والـ properties المرتبطة بها بـ Livewire
        const dateFields = [
            { id: 'start_date_input', wireProp: 'start_date' },
            { id: 'end_date_input', wireProp: 'end_date' },
            { id: 'edit_start_date_input', wireProp: 'editStartDate' },
            { id: 'edit_end_date_input', wireProp: 'editEndDate' }
        ];

        const timeFields = [
            { id: 'start_time_input', wireProp: 'start_time' },
            { id: 'end_time_input', wireProp: 'end_time' },
            { id: 'edit_start_time_input', wireProp: 'editStartTime' },
            { id: 'edit_end_time_input', wireProp: 'editEndTime' }
        ];

        // تهيئة حقول التاريخ
        dateFields.forEach(field => {
            const el = document.getElementById(field.id);
            if (!el || flatpickrInstances[field.id]) return;

            flatpickrInstances[field.id] = flatpickr(el, {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                locale: arabicLocale,
                disableMobile: true,
                allowInput: false,
                defaultDate: el.value || null,
                onChange: function(selectedDates, dateStr) {
                    // تحديث Livewire property مباشرة
                    $wire.set(field.wireProp, dateStr);
                }
            });
        });

        // تهيئة حقول الوقت (12 ساعة)
        timeFields.forEach(field => {
            const el = document.getElementById(field.id);
            if (!el || flatpickrInstances[field.id]) return;

            flatpickrInstances[field.id] = flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                altInput: true,
                altFormat: 'h:i K',
                time_24hr: false,
                locale: arabicLocale,
                disableMobile: true,
                allowInput: false,
                minuteIncrement: 5,
                defaultDate: el.value || null,
                onChange: function(selectedDates, dateStr) {
                    $wire.set(field.wireProp, dateStr);
                }
            });
        });
    }

    // عند فتح المودال: تهيئة
    document.addEventListener('shown.bs.modal', (e) => {
        if (e.target.id === 'createEventModal' || e.target.id === 'editEventModal') {
            setTimeout(initFlatpickr, 50);
        }
    });

    // عند إغلاق المودال: تنظيف
    document.addEventListener('hidden.bs.modal', (e) => {
        if (e.target.id === 'createEventModal' || e.target.id === 'editEventModal') {
            destroyFlatpickr();
        }
    });
</script>
@endscript

</div>
