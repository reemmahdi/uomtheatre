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

        {{-- فلتر الحالة (✨ أسماء عربية ثابتة - بدون under_review) --}}
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">
                <i class="bi bi-flag"></i> الحالة
            </label>
            <select wire:model.live="filterStatus" class="form-select form-select-sm">
                <option value="">— كل الحالات —</option>
                @php
                    $statusLabels = [
                        'draft'     => 'مسودة',
                        'added'     => 'مضافة',
                        'active'    => 'نشطة',
                        'published' => 'منشورة',
                        'closed'    => 'مغلقة',
                        'cancelled' => 'ملغاة',
                        'end'       => 'منتهية',
                    ];
                @endphp
                @foreach($allStatuses as $st)
                    @if(isset($statusLabels[$st->name]))
                    <option value="{{ $st->name }}">{{ $statusLabels[$st->name] }}</option>
                    @endif
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

{{-- ══════════════════════════════════════════════ --}}
{{--  ✨ الجدول الجديد - 6 أعمدة منظمة             --}}
{{-- ══════════════════════════════════════════════ --}}
<div class="card-custom p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 events-table">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th>عنوان الفعالية</th>
                    <th style="width: 150px;" class="text-center">موعد الانطلاق</th>
                    <th style="width: 150px;" class="text-center">موعد الاختتام</th>
                    <th style="width: 130px;" class="text-center">الحالة</th>
                    <th style="width: 220px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                @php
                    $statusColors = ['draft'=>'#6B7280','added'=>'#3B82F6','active'=>'#8B5CF6','published'=>'#10B981','closed'=>'#EF4444','cancelled'=>'#DC2626','end'=>'#9CA3AF'];
                    $statusNames = ['draft'=>'مسودة','added'=>'مضافة','active'=>'نشطة','published'=>'منشورة','closed'=>'مغلقة','cancelled'=>'ملغاة','end'=>'منتهية'];
                    $sName = $event->status->name;
                    $sColor = $statusColors[$sName] ?? '#6B7280';
                    $sLabel = $statusNames[$sName] ?? $sName;
                    $isCancelled = ($sName === 'cancelled');
                    $isPaused = $event->is_booking_paused;
                @endphp
                <tr class="@if($isCancelled) row-cancelled @elseif($isPaused) row-paused @endif">
                    {{-- # --}}
                    <td class="text-center">
                        <strong class="row-number">{{ $events->firstItem() + $loop->index }}</strong>
                    </td>

                    {{-- عنوان الفعالية --}}
                    <td>
                        <div class="event-title-cell">
                            <strong class="event-title-text @if($isCancelled) cancelled-title @endif">
                                @if($isCancelled) <i class="bi bi-x-octagon-fill text-danger"></i> @endif
                                @if($isPaused && !$isCancelled) <i class="bi bi-pause-circle-fill text-warning"></i> @endif
                                {{ $event->title }}
                            </strong>
                            @if($event->description)
                            <small class="text-muted d-block mt-1">{{ \Illuminate\Support\Str::limit($event->description, 60) }}</small>
                            @endif
                            {{-- سبب الإلغاء --}}
                            @if($isCancelled && $event->cancellation_reason)
                            <small class="text-muted fst-italic d-block mt-1">
                                <i class="bi bi-info-circle"></i> سبب الإلغاء: {{ \Illuminate\Support\Str::limit($event->cancellation_reason, 60) }}
                            </small>
                            @endif
                            {{-- تنبيه الإيقاف المؤقت --}}
                            @if($isPaused && !$isCancelled)
                            <small class="text-warning fst-italic d-block mt-1">
                                <i class="bi bi-pause-fill"></i> الحجز متوقف مؤقتاً
                                @if($event->paused_at)
                                    — منذ {{ $event->paused_at->format('Y-m-d H:i') }}
                                @endif
                            </small>
                            @endif
                        </div>
                    </td>

                    {{-- ✨ موعد الانطلاق --}}
                    <td class="text-center">
                        <div class="date-cell">
                            <div class="date-day" dir="ltr">{{ $event->start_datetime->format('Y-m-d') }}</div>
                            <div class="date-time" dir="ltr">{{ $event->start_datetime->format('H:i') }}</div>
                        </div>
                    </td>

                    {{-- ✨ موعد الاختتام --}}
                    <td class="text-center">
                        <div class="date-cell">
                            <div class="date-day" dir="ltr">{{ $event->end_datetime->format('Y-m-d') }}</div>
                            <div class="date-time" dir="ltr">{{ $event->end_datetime->format('H:i') }}</div>
                        </div>
                    </td>

                    {{-- الحالة --}}
                    <td class="text-center">
                        <div class="status-stack">
                            <span class="status-badge status-badge-{{ $sName }}">
                                @if($isCancelled) <i class="bi bi-x-octagon-fill"></i> @endif
                                {{ $sLabel }}
                            </span>
                            @if($isPaused && !$isCancelled)
                            <span class="status-badge status-badge-paused">
                                <i class="bi bi-pause-circle-fill"></i> متوقفة
                            </span>
                            @endif
                        </div>
                    </td>

                    {{-- ══════════════════════════════════════════════ --}}
                    {{--  ✨ خلية الإجراءات                              --}}
                    {{-- ══════════════════════════════════════════════ --}}
                    <td class="text-center">
                        <div class="actions-group">
                            {{-- 1. عرض التفاصيل (دائماً) --}}
                            <button type="button"
                                    class="btn-action btn-action-view"
                                    wire:click="viewEvent({{ $event->id }})"
                                    title="عرض التفاصيل">
                                <i class="bi bi-eye"></i>
                            </button>

                            {{-- 2. مدير المسرح: تعديل + إرسال للمراجعة (للمسودات فقط) --}}
                            @if(in_array($roleName, ['super_admin', 'theater_manager']))
                                @if($sName === 'draft')
                                <button type="button"
                                        class="btn-action btn-action-edit"
                                        wire:click="openEdit({{ $event->id }})"
                                        title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button"
                                        class="btn-action btn-action-send"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'added')"
                                        title="إرسال للمراجعة">
                                    <i class="bi bi-send"></i>
                                </button>
                                @endif
                            @endif

                            {{-- 3. مدير الإعلام: قبول / نشر / إغلاق / وفود --}}
                            @if(in_array($roleName, ['super_admin', 'event_manager']))
                                @if($sName === 'added')
                                <button type="button"
                                        class="btn-action btn-action-approve"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'active')"
                                        title="قبول الفعالية">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                @endif

                                @if(in_array($sName, ['active', 'published']))
                                <a href="{{ route('dashboard.vip-booking', $event->uuid) }}"
                                   class="btn-action btn-action-vip"
                                   title="إدارة مقاعد الوفود">
                                    <i class="bi bi-star-fill"></i>
                                </a>
                                @endif

                                @if($sName === 'active')
                                <button type="button"
                                        class="btn-action btn-action-publish"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'published')"
                                        title="نشر الفعالية">
                                    <i class="bi bi-megaphone-fill"></i>
                                </button>
                                @endif

                                @if($sName === 'published')
                                <button type="button"
                                        class="btn-action btn-action-close"
                                        wire:click="requestChangeStatus({{ $event->id }}, 'closed')"
                                        title="إغلاق الفعالية">
                                    <i class="bi bi-lock-fill"></i>
                                </button>

                                {{-- زر الإيقاف/الاستئناف --}}
                                @if(!$isPaused)
                                <button type="button"
                                        class="btn-action btn-action-pause"
                                        wire:click="requestPauseBooking({{ $event->id }})"
                                        title="إيقاف الحجز مؤقتاً">
                                    <i class="bi bi-pause-circle-fill"></i>
                                </button>
                                @else
                                <button type="button"
                                        class="btn-action btn-action-resume"
                                        wire:click="requestResumeBooking({{ $event->id }})"
                                        title="استئناف الحجز">
                                    <i class="bi bi-play-circle-fill"></i>
                                </button>
                                @endif
                                @endif
                            @endif

                            {{-- زر الإلغاء --}}
                            @if(!in_array($sName, ['cancelled', 'end', 'closed']))
                            <button type="button"
                                    class="btn-action btn-action-cancel"
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
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x" style="font-size:50px; color:#cbd5e1;"></i>
                        @if($searchTitle || $filterStatus || $filterDateFrom || $filterDateTo)
                        <p class="mt-3 mb-2">لا توجد فعاليات مطابقة لمعايير البحث</p>
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-counterclockwise"></i> مسح الفلاتر
                        </button>
                        @else
                        <p class="mt-3">لا توجد فعاليات</p>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ✨ روابط التصفّح --}}
    @if($events->hasPages())
    <div class="pagination-wrapper p-3 border-top">
        <div class="pagination-links-only d-flex justify-content-center">
            {{ $events->onEachSide(1)->links() }}
        </div>
    </div>
    @endif
</div>


{{-- ══════════════════════════════════════════════ --}}
{{--  Modals                                         --}}
{{-- ══════════════════════════════════════════════ --}}

{{-- نافذة عرض التفاصيل --}}
<div class="modal fade" id="viewEventModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye"></i> تفاصيل الفعالية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if(!empty($showEvent))

                {{-- عنوان الفعالية أولاً --}}
                <div class="mb-3 p-3 rounded" style="background: linear-gradient(135deg, #e0f2fe, #f0f9ff); border-right: 4px solid #0C4A6E;">
                    <h5 style="color: #0C4A6E; font-weight: 700; margin: 0;">{{ $showEvent['title'] }}</h5>
                </div>

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

                {{-- تنبيه الإيقاف --}}
                @if(!empty($showEvent['is_booking_paused']))
                <div class="alert alert-warning border-warning mb-3">
                    <h6 class="alert-heading mb-2">
                        <i class="bi bi-pause-circle-fill"></i> الحجز غير متاح حالياً
                    </h6>
                    <p class="mb-2 small">الحجوزات الجديدة متوقفة، لكن الحجوزات السابقة محفوظة.</p>
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> إنشاء فعالية جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> تعديل الفعالية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
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

{{-- نافذة إلغاء الفعالية --}}
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


{{-- ══════════════════════════════════════════════ --}}
{{--  ✨ تنسيقات الجدول - باليتة Midnight Ocean    --}}
{{-- ══════════════════════════════════════════════ --}}
<style>
    /* ════════ متغيرات اللوحة الرئيسية ════════ */
    .events-table {
        --c-primary: #0C4A6E;
        --c-primary-light: #075985;
        --c-primary-lighter: #0369A1;
        --c-primary-soft: #e0f2fe;
        --c-primary-softer: #f0f9ff;
        --c-danger: #DC2626;
        --c-danger-soft: #fef2f2;
        --c-text: #1e293b;
        --c-text-muted: #64748b;
        --c-border: #e2e8f0;
        --c-border-light: #f1f5f9;
        font-size: 14px;
    }

    /* ════════ رأس الجدول ════════ */
    .events-table thead {
        background: var(--c-primary);
    }

    .events-table thead th {
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        padding: 14px 12px;
        border: none;
        white-space: nowrap;
        letter-spacing: 0.5px;
    }

    /* ════════ خلايا الجدول ════════ */
    .events-table tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--c-border-light);
        color: var(--c-text);
    }

    .events-table tbody tr {
        transition: background-color 0.15s ease;
    }

    .events-table tbody tr:hover {
        background-color: var(--c-primary-softer) !important;
    }

    /* ════════ الصفوف الخاصة ════════ */
    .events-table tr.row-cancelled {
        background-color: #fafafa;
    }

    .events-table tr.row-cancelled:hover {
        background-color: #f4f4f5 !important;
    }

    .events-table tr.row-paused {
        background-color: var(--c-primary-softer);
    }

    /* ════════ الترقيم ════════ */
    .row-number {
        display: inline-block;
        width: 32px;
        height: 32px;
        line-height: 28px;
        background: #fff;
        border: 2px solid var(--c-primary);
        border-radius: 50%;
        color: var(--c-primary);
        font-size: 13px;
        font-weight: 700;
    }

    /* ════════ خلية العنوان ════════ */
    .event-title-cell {
        max-width: 380px;
    }

    .event-title-text {
        color: var(--c-primary);
        font-size: 15px;
        line-height: 1.5;
    }

    .event-title-text.cancelled-title {
        color: var(--c-text-muted);
    }

    /* ════════ خلية المواعيد (بسيطة - بدون ألوان أو زخرفة) ════════ */
    .date-cell {
        display: inline-block;
        white-space: nowrap;
        line-height: 1.5;
    }

    .date-day {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
        letter-spacing: 0.3px;
    }

    .date-time {
        font-size: 12px;
        font-weight: 500;
        color: var(--c-text-muted);
        margin-top: 2px;
    }

    /* ════════ Status Badges (Dark Solid - باليتة موحّدة) ════════ */
    .status-stack {
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: center;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 14px;
        border-radius: 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.3px;
        white-space: nowrap;
        border: none;
        color: #fff !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
        box-shadow: 0 2px 4px rgba(12, 74, 110, 0.15);
        min-width: 80px;
    }

    /* الحالات الإيجابية: تدرّج من نفس اللون الأساسي */
    .status-badge-draft {
        background: #94a3b8;
    }

    .status-badge-added {
        background: #075985;
    }

    .status-badge-active {
        background: #0369A1;
    }

    .status-badge-published {
        background: #0C4A6E;
    }

    .status-badge-closed {
        background: #475569;
    }

    /* الحالات السلبية: لون أحمر/رمادي فقط */
    .status-badge-cancelled {
        background: #DC2626;
    }

    .status-badge-end {
        background: #6b7280;
    }

    .status-badge-paused {
        background: #92400E;
    }

    /* ════════ مجموعة الإجراءات ════════ */
    .events-table .actions-group {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 5px;
        justify-content: center;
        align-items: center;
    }

    .events-table .btn-action {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 8px !important;
        border: 1.5px solid !important;
        background: #fff !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
        text-decoration: none !important;
        line-height: 1 !important;
        font-size: 16px !important;
    }

    .events-table .btn-action i,
    .events-table .btn-action [class^="bi-"],
    .events-table .btn-action [class*=" bi-"] {
        font-size: 16px !important;
        line-height: 1 !important;
        display: inline-block !important;
        vertical-align: middle !important;
        font-style: normal !important;
        font-weight: normal !important;
        color: inherit !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .events-table .btn-action:hover {
        transform: translateY(-2px);
        text-decoration: none !important;
        box-shadow: 0 4px 8px rgba(12, 74, 110, 0.18);
    }

    /* ════════ ألوان الأزرار - باليتة موحّدة (3 ألوان فقط) ════════ */
    /* الأزرار الإيجابية (عرض، تعديل، إرسال، قبول، نشر، استئناف، وفود، إغلاق): أزرق داكن */
    .events-table .btn-action.btn-action-view,
    .events-table .btn-action.btn-action-edit,
    .events-table .btn-action.btn-action-send,
    .events-table .btn-action.btn-action-approve,
    .events-table .btn-action.btn-action-vip,
    .events-table .btn-action.btn-action-publish,
    .events-table .btn-action.btn-action-close,
    .events-table .btn-action.btn-action-resume {
        color: var(--c-primary) !important;
        border-color: var(--c-primary) !important;
    }

    .events-table .btn-action.btn-action-view:hover,
    .events-table .btn-action.btn-action-edit:hover,
    .events-table .btn-action.btn-action-send:hover,
    .events-table .btn-action.btn-action-approve:hover,
    .events-table .btn-action.btn-action-vip:hover,
    .events-table .btn-action.btn-action-publish:hover,
    .events-table .btn-action.btn-action-close:hover,
    .events-table .btn-action.btn-action-resume:hover {
        background: var(--c-primary) !important;
        color: #fff !important;
    }

    /* زر الإيقاف: لون متوسط من نفس الباليتة */
    .events-table .btn-action.btn-action-pause {
        color: var(--c-primary-lighter) !important;
        border-color: var(--c-primary-lighter) !important;
    }
    .events-table .btn-action.btn-action-pause:hover {
        background: var(--c-primary-lighter) !important;
        color: #fff !important;
    }

    /* زر الإلغاء: أحمر (الوحيد المتميّز - للخطر فقط) */
    .events-table .btn-action.btn-action-cancel {
        color: var(--c-danger) !important;
        border-color: var(--c-danger) !important;
    }
    .events-table .btn-action.btn-action-cancel:hover {
        background: var(--c-danger) !important;
        color: #fff !important;
    }

    /* ════════ Responsive ════════ */
    @media (max-width: 992px) {
        .events-table {
            font-size: 13px;
        }

        .event-title-cell {
            max-width: 240px;
        }

        .events-table .btn-action {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            font-size: 14px !important;
        }

        .events-table .btn-action i,
        .events-table .btn-action [class^="bi-"] {
            font-size: 14px !important;
        }

        /* تصغير خلية المواعيد على الشاشات الصغيرة */
        .date-day {
            font-size: 12px;
        }

        .date-time {
            font-size: 11px;
        }
    }

    /* ════════════════════════════════════════════════════════════════
       📱 Mobile Card Layout (تحويل الجدول لكروت على الجوال < 768px)
       ════════════════════════════════════════════════════════════════ */
    @media (max-width: 767.98px) {
        /* إخفاء الـ thead على الجوال - الكروت تبيّن نفسها */
        .events-table thead {
            display: none;
        }

        /* إعادة تنسيق الجدول */
        .events-table,
        .events-table tbody,
        .events-table tr,
        .events-table td {
            display: block;
            width: 100%;
        }

        /* إخفاء الحد بين الصفوف */
        .events-table tr {
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(12, 74, 110, 0.06);
            padding: 0;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .events-table tr:hover {
            box-shadow: 0 4px 14px rgba(12, 74, 110, 0.12);
        }

        /* الفعاليات الملغاة - حدود حمراء */
        .events-table tr.row-cancelled {
            border-color: #fecaca;
            background: #fef2f2;
        }

        /* الفعاليات الموقوفة - حدود برتقالية */
        .events-table tr.row-paused {
            border-color: #fed7aa;
            background: #fffbeb;
        }

        /* كل خلية تصير صف داخل الكرت */
        .events-table td {
            border: none !important;
            padding: 10px 14px !important;
            text-align: start !important;
            position: relative;
            min-height: 36px;
        }

        /* خط فاصل بين الخلايا داخل الكرت */
        .events-table td:not(:last-child) {
            border-bottom: 1px solid #f1f5f9 !important;
        }

        /* خلية الترقيم # - تصير في رأس الكرت مع الحالة */
        .events-table td:first-child {
            display: inline-block;
            width: auto !important;
            background: linear-gradient(135deg, #0C4A6E, #075985);
            color: #fff;
            border-radius: 14px 14px 0 0 !important;
            text-align: start !important;
            padding: 12px 16px !important;
        }

        .events-table td:first-child::before {
            content: "فعالية رقم: ";
            font-size: 11px;
            font-weight: 600;
            opacity: 0.85;
            margin-inline-end: 4px;
        }

        .events-table td:first-child .row-number {
            font-size: 16px;
            font-weight: 800;
            color: #fff !important;
        }

        /* خلية العنوان - بارزة */
        .events-table td:nth-child(2) {
            background: #f8fafc;
            font-size: 14px;
        }

        .event-title-text {
            font-size: 15px !important;
        }

        /* خلايا التاريخ - تخطيط أفقي مع ليبل */
        .events-table td:nth-child(3),
        .events-table td:nth-child(4) {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 12px;
        }

        .events-table td:nth-child(3)::before {
            content: "🟢 موعد الانطلاق:";
            font-weight: 700;
            color: #0C4A6E;
            font-size: 13px;
            flex-shrink: 0;
        }

        .events-table td:nth-child(4)::before {
            content: "🔴 موعد الاختتام:";
            font-weight: 700;
            color: #0C4A6E;
            font-size: 13px;
            flex-shrink: 0;
        }

        .events-table td:nth-child(3) .date-cell,
        .events-table td:nth-child(4) .date-cell {
            text-align: end;
        }

        /* خلية الحالة - تخطيط أفقي */
        .events-table td:nth-child(5) {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 12px;
        }

        .events-table td:nth-child(5)::before {
            content: "الحالة:";
            font-weight: 700;
            color: #0C4A6E;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* خلية الإجراءات - في الأسفل بشكل بارز */
        .events-table td:last-child {
            background: #f8fafc;
            text-align: center !important;
            padding: 12px 14px !important;
        }

        .events-table td:last-child::before {
            content: "";
        }

        /* الأزرار في خلية الإجراءات - أكبر ومتباعدة */
        .events-table td:last-child .btn-action {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            margin: 2px;
            font-size: 16px !important;
        }

        .events-table td:last-child .btn-action i,
        .events-table td:last-child .btn-action [class^="bi-"] {
            font-size: 16px !important;
        }

        /* تكبير badge الحالة على الجوال */
        .status-badge {
            font-size: 13px;
            padding: 6px 14px;
        }

        /* إصلاح الـ Pagination على الجوال */
        .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }

        /* إصلاح فلاتر البحث على الجوال */
        .filters-section .row > * {
            margin-bottom: 8px;
        }
    }
</style>


<script>
document.addEventListener('livewire:initialized', () => {
    // عند إطلاق حدث close-modal، أغلق كل الـ modals
    Livewire.on('close-modal', () => {
        document.querySelectorAll('.modal').forEach(m => {
            const inst = bootstrap.Modal.getInstance(m);
            if (inst) inst.hide();
        });
    });

    // ✨ عند إطلاق حدث open-view-modal، افتح modal التفاصيل
    Livewire.on('open-view-modal', () => {
        const modalEl = document.getElementById('viewEventModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    // ✨ عند إطلاق حدث open-edit-modal، افتح modal التعديل (بعد ما البيانات جاهزة)
    Livewire.on('open-edit-modal', () => {
        const modalEl = document.getElementById('editEventModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });
});

// ═══════════════════════════════════════════════════
// ✨ تهيئة Flatpickr
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

/**
 * ✨ يقرأ القيمة الحالية من Livewire مباشرة (وليس من DOM)
 * هذا يحل مشكلة الحقول الفارغة في نافذة التعديل
 */
function getLivewireValue(propName) {
    try {
        const wireEl = document.querySelector('[wire\\:id]');
        if (!wireEl) return null;
        const wire = Livewire.find(wireEl.getAttribute('wire:id'));
        if (!wire) return null;
        const val = wire.get(propName);
        return (val && String(val).trim() !== '' && String(val).trim() !== '00:00') ? String(val) : null;
    } catch (e) {
        return null;
    }
}

function initFlatpickr() {
    if (typeof flatpickr === 'undefined') return;

    const arabicLocale = (flatpickr.l10ns && flatpickr.l10ns.ar) ? flatpickr.l10ns.ar : 'default';

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

    // ✨ تهيئة حقول التاريخ
    dateFields.forEach(field => {
        const el = document.getElementById(field.id);
        if (!el || flatpickrInstances[field.id]) return;

        // ✅ نقرأ من Livewire أولاً، ثم من DOM كـ fallback
        let initialValue = getLivewireValue(field.wireProp);
        if (!initialValue && el.value && el.value.trim() !== '') {
            initialValue = el.value;
        }

        flatpickrInstances[field.id] = flatpickr(el, {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            locale: arabicLocale,
            disableMobile: true,
            allowInput: true,
            defaultDate: initialValue,
            onChange: function(selectedDates, dateStr) {
                if (window.Livewire) {
                    const wire = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (wire) wire.set(field.wireProp, dateStr);
                }
            },
            onClose: function(selectedDates, dateStr) {
                if (dateStr && window.Livewire) {
                    const wire = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (wire) wire.set(field.wireProp, dateStr);
                }
            }
        });
    });

    // ✨ تهيئة حقول الوقت (12 ساعة - بدون وقت افتراضي 00:00)
    timeFields.forEach(field => {
        const el = document.getElementById(field.id);
        if (!el || flatpickrInstances[field.id]) return;

        // ✅ نقرأ من Livewire أولاً، ثم من DOM كـ fallback
        let initialValue = getLivewireValue(field.wireProp);
        if (!initialValue) {
            const rawValue = el.value ? el.value.trim() : '';
            initialValue = (rawValue !== '' && rawValue !== '00:00') ? rawValue : null;
        }

        flatpickrInstances[field.id] = flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'h:i K',
            time_24hr: false,
            locale: arabicLocale,
            disableMobile: true,
            allowInput: true,
            minuteIncrement: 5,
            defaultDate: initialValue,
            onChange: function(selectedDates, dateStr) {
                if (dateStr && selectedDates.length > 0 && window.Livewire) {
                    const wire = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (wire) wire.set(field.wireProp, dateStr);
                }
            },
            onClose: function(selectedDates, dateStr) {
                if (dateStr && selectedDates.length > 0 && window.Livewire) {
                    const wire = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                    if (wire) wire.set(field.wireProp, dateStr);
                }
            }
        });
    });
}

// ✨ تهيئة Flatpickr عند فتح modal الإنشاء أو التعديل
// مع تأخير أكبر لـ editEventModal لضمان أن Livewire حدّث القيم
document.addEventListener('shown.bs.modal', function(e) {
    if (e.target.id === 'createEventModal') {
        setTimeout(initFlatpickr, 50);
    } else if (e.target.id === 'editEventModal') {
        // ✅ تأخير أطول للتعديل + إعادة تهيئة بعد التأكد من تحديث القيم
        setTimeout(() => {
            destroyFlatpickr(); // نهدم القديم قبل التهيئة الجديدة
            initFlatpickr();
        }, 200);
    }
});

// ✨ تنظيف Flatpickr عند إغلاق الـ modal
document.addEventListener('hidden.bs.modal', function(e) {
    if (e.target.id === 'createEventModal' || e.target.id === 'editEventModal') {
        destroyFlatpickr();
    }
});

// ✨ إعادة تهيئة Flatpickr بعد كل تحديث Livewire (للنافذة المفتوحة)
document.addEventListener('livewire:initialized', () => {
    Livewire.hook('morph.updated', ({ el, component }) => {
        // إذا كانت نافذة التعديل مفتوحة، أعد تهيئة Flatpickr
        const editModal = document.getElementById('editEventModal');
        if (editModal && editModal.classList.contains('show')) {
            setTimeout(() => {
                destroyFlatpickr();
                initFlatpickr();
            }, 100);
        }
    });
});
</script>

</div>
