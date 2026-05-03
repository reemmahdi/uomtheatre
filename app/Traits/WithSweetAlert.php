<?php

namespace App\Traits;

/**
 * ============================================================
 * WithSweetAlert Trait - UOMTheatre Dashboard
 * ============================================================
 * Trait مركزي لإرسال SweetAlert events من Livewire
 * 
 * طريقة الاستخدام:
 * ----------------
 * في أي Livewire Component:
 * 
 * use App\Traits\WithSweetAlert;
 * 
 * class Events extends Component
 * {
 *     use WithSweetAlert;
 * 
 *     public function save()
 *     {
 *         // ... منطق الحفظ
 *         $this->swalSuccess('تم حفظ الفعالية بنجاح');
 *     }
 * }
 * ============================================================
 */
trait WithSweetAlert
{
    /**
     * إرسال رسالة نجاح
     * 
     * @param string $message الرسالة
     * @param string|null $title العنوان (اختياري)
     */
    public function swalSuccess(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:success', [
            'message' => $message,
            'title' => $title ?? 'تم بنجاح',
        ]);
    }

    /**
     * إرسال رسالة خطأ
     * 
     * @param string $message الرسالة
     * @param string|null $title العنوان (اختياري)
     */
    public function swalError(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:error', [
            'message' => $message,
            'title' => $title ?? 'حدث خطأ',
        ]);
    }

    /**
     * إرسال رسالة تحذير
     * 
     * @param string $message الرسالة
     * @param string|null $title العنوان (اختياري)
     */
    public function swalWarning(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:warning', [
            'message' => $message,
            'title' => $title ?? 'تنبيه',
        ]);
    }

    /**
     * إرسال رسالة معلوماتية
     * 
     * @param string $message الرسالة
     * @param string|null $title العنوان (اختياري)
     */
    public function swalInfo(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:info', [
            'message' => $message,
            'title' => $title ?? 'معلومة',
        ]);
    }

    /**
     * إرسال Toast (تنبيه صغير علوي يختفي تلقائياً)
     * 
     * @param string $message الرسالة
     * @param string $icon نوع الأيقونة (success, error, warning, info)
     */
    public function swalToast(string $message, string $icon = 'success'): void
    {
        $this->dispatch('swal:toast', [
            'message' => $message,
            'icon' => $icon,
        ]);
    }

    /**
     * إرسال نافذة تأكيد قبل الإجراءات الخطيرة (كالحذف)
     * 
     * @param string $message رسالة التأكيد
     * @param string $action اسم الـ method اللي راح يشتغل بعد التأكيد
     * @param mixed $params البارامترات المرسلة للـ method
     * @param string|null $title العنوان
     * 
     * مثال:
     * $this->swalConfirm(
     *     'هل أنت متأكد من حذف الفعالية؟',
     *     'confirmDelete',
     *     $eventId
     * );
     * 
     * ثم في نفس الـ Component:
     * #[On('confirmDelete')]
     * public function confirmDelete($id) { ... }
     */
    public function swalConfirm(
        string $message,
        string $action,
        mixed $params = null,
        ?string $title = null
    ): void {
        $this->dispatch('swal:confirm', [
            'message' => $message,
            'action' => $action,
            'params' => $params,
            'title' => $title ?? 'هل أنت متأكد؟',
        ]);
    }
}
