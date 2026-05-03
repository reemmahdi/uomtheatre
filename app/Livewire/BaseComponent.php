<?php

namespace App\Livewire;

use Livewire\Component;
use App\Traits\WithSweetAlert;

/**
 * ============================================================
 * BaseComponent - UOMTheatre Dashboard
 * ============================================================
 * Component أساسي لجميع Livewire Components في الداشبورد
 * 
 * أي Component يرث من هذا الـ BaseComponent يحصل تلقائياً على:
 * - swalSuccess()
 * - swalError()
 * - swalWarning()
 * - swalInfo()
 * - swalToast()
 * - swalConfirm()
 * 
 * طريقة الاستخدام:
 * ----------------
 * بدلاً من:
 *   use Livewire\Component;
 *   class Events extends Component { ... }
 * 
 * استخدمي:
 *   use App\Livewire\BaseComponent;
 *   class Events extends BaseComponent { ... }
 * 
 * وراح تحصلي على كل دوال SweetAlert تلقائياً
 * ============================================================
 */
abstract class BaseComponent extends Component
{
    use WithSweetAlert;

    // في المستقبل: ممكن نضيف هنا دوال مشتركة أخرى
    // مثل: logs، permissions، formatting
}
