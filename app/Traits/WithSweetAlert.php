<?php

namespace App\Traits;

trait WithSweetAlert
{
    public function swalSuccess(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:success', [
            'message' => $message,
            'title' => $title ?? 'تم بنجاح',
        ]);
    }

    public function swalError(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:error', [
            'message' => $message,
            'title' => $title ?? 'حدث خطأ',
        ]);
    }

    public function swalWarning(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:warning', [
            'message' => $message,
            'title' => $title ?? 'تنبيه',
        ]);
    }

    public function swalInfo(string $message, ?string $title = null): void
    {
        $this->dispatch('swal:info', [
            'message' => $message,
            'title' => $title ?? 'معلومة',
        ]);
    }

    public function swalToast(string $message, string $icon = 'success'): void
    {
        $this->dispatch('swal:toast', [
            'message' => $message,
            'icon' => $icon,
        ]);
    }

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
