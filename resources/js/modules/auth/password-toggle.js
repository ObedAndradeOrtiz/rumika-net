document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');

    if (! toggle) {
        return;
    }

    const input = document.querySelector(toggle.dataset.passwordToggle);

    if (! input) {
        return;
    }

    input.type = input.type === 'password' ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', input.type === 'text' ? 'true' : 'false');
});
