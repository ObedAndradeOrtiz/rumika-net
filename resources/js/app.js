import './bootstrap';
import './modules/auth/password-toggle';
import './modules/auth/firebase-google';
import './modules/app-shell/sidebar-toggle';
import './modules/app-shell/mobile-more';
import './modules/printing/qz-tray';

document.addEventListener('livewire:init', () => {
    window.Livewire?.on('branch-switched', () => {
        window.location.reload();
    });
});
