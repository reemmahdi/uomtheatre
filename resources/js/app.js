import './bootstrap';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import { Html5Qrcode, Html5QrcodeScanner } from 'html5-qrcode';
import './sweet-alert-helper';

window.bootstrap = bootstrap;
window.Swal = Swal;
window.flatpickr = flatpickr;
flatpickr.l10ns.ar = Arabic;
window.Html5Qrcode = Html5Qrcode;
window.Html5QrcodeScanner = Html5QrcodeScanner;
