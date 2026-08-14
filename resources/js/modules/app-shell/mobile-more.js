const setMobileMoreOpen = (open) => {
    const panel = document.querySelector('[data-mobile-more-panel]');
    const toggle = document.querySelector('[data-mobile-more-toggle]');

    if (! panel || ! toggle) {
        return;
    }

    panel.classList.toggle('is-open', open);
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-more-toggle]')) {
        const panel = document.querySelector('[data-mobile-more-panel]');
        setMobileMoreOpen(! panel?.classList.contains('is-open'));

        return;
    }

    if (event.target.closest('[data-mobile-more-close]')) {
        setMobileMoreOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMobileMoreOpen(false);
    }
});
