/**
 * SweetAlert2 Helper - UOMTheatre Dashboard
 * Midnight Ocean Theme
 */

// Default configuration
const SwalDefaults = {
    customClass: {
        popup: 'swal-rtl',
        title: 'swal-title-custom',
        confirmButton: 'swal-confirm-btn',
        cancelButton: 'swal-cancel-btn',
    },
    buttonsStyling: true,
    reverseButtons: false,
};

// Midnight Ocean color palette
const Colors = {
    primary: '#0C4A6E',
    primaryMid: '#075985',
    primaryLight: '#0369A1',
    gold: '#E4C05E',
    goldDark: '#C9A445',
    success: '#0C4A6E',
    danger: '#DC2626',
    warning: '#E4C05E',
    info: '#0369A1',
};

// Arabic labels (Unicode escapes to avoid encoding issues)
const Labels = {
    ok: '\u062D\u0633\u0646\u0627\u064B',
    yes: '\u0646\u0639\u0645\u060C \u0645\u062A\u0623\u0643\u062F',
    cancel: '\u0625\u0644\u063A\u0627\u0621',
    successTitle: '\u062A\u0645 \u0628\u0646\u062C\u0627\u062D',
    errorTitle: '\u062D\u062F\u062B \u062E\u0637\u0623',
    warningTitle: '\u062A\u0646\u0628\u064A\u0647',
    infoTitle: '\u0645\u0639\u0644\u0648\u0645\u0629',
    confirmTitle: '\u0647\u0644 \u0623\u0646\u062A \u0645\u062A\u0623\u0643\u062F\u061F',
    successDefault: '\u062A\u0645\u062A \u0627\u0644\u0639\u0645\u0644\u064A\u0629 \u0628\u0646\u062C\u0627\u062D',
    errorDefault: '\u062D\u062F\u062B \u062E\u0637\u0623 \u063A\u064A\u0631 \u0645\u062A\u0648\u0642\u0639',
};

// Toast configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'swal-rtl swal-toast-custom',
    },
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

// Helper functions

function showSuccess(message, title) {
    return Swal.fire({
        ...SwalDefaults,
        icon: 'success',
        title: title || Labels.successTitle,
        text: message,
        confirmButtonText: Labels.ok,
        confirmButtonColor: Colors.primary,
        timer: 3000,
        timerProgressBar: true,
    });
}

function showError(message, title) {
    return Swal.fire({
        ...SwalDefaults,
        icon: 'error',
        title: title || Labels.errorTitle,
        text: message,
        confirmButtonText: Labels.ok,
        confirmButtonColor: Colors.danger,
    });
}

function showWarning(message, title) {
    return Swal.fire({
        ...SwalDefaults,
        icon: 'warning',
        title: title || Labels.warningTitle,
        text: message,
        confirmButtonText: Labels.ok,
        confirmButtonColor: Colors.gold,
    });
}

function showInfo(message, title) {
    return Swal.fire({
        ...SwalDefaults,
        icon: 'info',
        title: title || Labels.infoTitle,
        text: message,
        confirmButtonText: Labels.ok,
        confirmButtonColor: Colors.primaryLight,
    });
}

function showToast(message, icon) {
    return Toast.fire({
        icon: icon || 'success',
        title: message,
    });
}

function showConfirm(message, action, params, title) {
    return Swal.fire({
        ...SwalDefaults,
        icon: 'warning',
        title: title || Labels.confirmTitle,
        text: message,
        showCancelButton: true,
        confirmButtonText: Labels.yes,
        cancelButtonText: Labels.cancel,
        confirmButtonColor: Colors.primary,
        cancelButtonColor: '#6c757d',
        focusCancel: true,
    }).then((result) => {
        if (result.isConfirmed) {
            if (params !== null && params !== undefined) {
                Livewire.dispatch(action, { id: params });
            } else {
                Livewire.dispatch(action);
            }
        }
    });
}

// Livewire Event Listeners
document.addEventListener('livewire:initialized', () => {

    Livewire.on('swal:success', (event) => {
        const data = event[0] || event;
        showSuccess(data.message || Labels.successDefault, data.title);
    });

    Livewire.on('swal:error', (event) => {
        const data = event[0] || event;
        showError(data.message || Labels.errorDefault, data.title);
    });

    Livewire.on('swal:warning', (event) => {
        const data = event[0] || event;
        showWarning(data.message, data.title);
    });

    Livewire.on('swal:info', (event) => {
        const data = event[0] || event;
        showInfo(data.message, data.title);
    });

    Livewire.on('swal:toast', (event) => {
        const data = event[0] || event;
        showToast(data.message, data.icon || 'success');
    });

    Livewire.on('swal:confirm', (event) => {
        const data = event[0] || event;
        showConfirm(
            data.message || Labels.confirmTitle,
            data.action,
            data.params || null,
            data.title
        );
    });

    console.log('SweetAlert Helper Loaded - Midnight Ocean Theme');
});

// Export for manual use
window.SwalHelper = {
    success: showSuccess,
    error: showError,
    warning: showWarning,
    info: showInfo,
    toast: showToast,
    confirm: showConfirm,
};
