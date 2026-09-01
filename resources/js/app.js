import './bootstrap';
import './modules/auth/password-toggle';
import './modules/auth/firebase-google';
import './modules/auth/face-security';
import './modules/app-shell/sidebar-toggle';
import './modules/app-shell/mobile-more';
import './modules/app-shell/theme-toggle';
import './modules/app-shell/booking-qr';
import './modules/app-shell/rumi-assistant';
import './modules/crm/chat-scroll';
import './modules/hr/attendance-punch';
import './modules/profile/avatar-cropper';
import './modules/printing/qz-tray';

document.addEventListener('livewire:init', () => {
    window.Livewire?.on('branch-switched', () => {
        window.location.reload();
    });

    window.Livewire?.on('attendance-saved', () => {
        window.setTimeout(() => window.location.reload(), 650);
    });
});
