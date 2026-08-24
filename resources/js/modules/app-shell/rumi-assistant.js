const safeActions = {
    new_appointment: '[data-rumi-action="new-appointment"]',
    new_client: '[data-rumi-action="new-client"]',
};

const clickWhenReady = (selector, attempts = 18) => {
    const target = document.querySelector(selector);

    if (target) {
        target.click();
        return;
    }

    if (attempts > 0) {
        window.setTimeout(() => clickWhenReady(selector, attempts - 1), 180);
    }
};

const runQueryAction = () => {
    const params = new URLSearchParams(window.location.search);
    const action = params.get('rumi_action');

    if (!action || !safeActions[action]) {
        return;
    }

    clickWhenReady(safeActions[action]);
    params.delete('rumi_action');

    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
    window.history.replaceState({}, '', nextUrl);
};

const clamp = (value, min, max) => Math.max(min, Math.min(value, max));

const applySavedRumiPosition = () => {
    const rumi = document.querySelector('[data-rumi-assistant]');

    if (!rumi) {
        return;
    }

    const saved = localStorage.getItem('rumi-position');

    if (!saved) {
        return;
    }

    try {
        const position = JSON.parse(saved);
        const x = clamp(Number(position.x) || 0, 8, window.innerWidth - rumi.offsetWidth - 8);
        const y = clamp(Number(position.y) || 0, 8, window.innerHeight - rumi.offsetHeight - 8);

        rumi.style.left = `${x}px`;
        rumi.style.top = `${y}px`;
        rumi.style.right = 'auto';
        rumi.style.bottom = 'auto';
        rumi.classList.add('is-custom-position');
    } catch {
        localStorage.removeItem('rumi-position');
    }
};

const initRumiDrag = () => {
    const rumi = document.querySelector('[data-rumi-assistant]');
    const handle = document.querySelector('[data-rumi-drag-handle]');

    if (!rumi || !handle || handle.dataset.rumiDragReady === '1') {
        return;
    }

    handle.dataset.rumiDragReady = '1';
    applySavedRumiPosition();

    let dragging = false;
    let startX = 0;
    let startY = 0;
    let originX = 0;
    let originY = 0;
    let moved = false;
    let suppressNextClick = false;

    const pointerPosition = (event) => {
        const point = event.touches?.[0] || event.changedTouches?.[0] || event;

        return { x: point.clientX, y: point.clientY };
    };

    const start = (event) => {
        if (event.target.closest('.rm-rumi-panel')) {
            return;
        }

        const point = pointerPosition(event);
        const rect = rumi.getBoundingClientRect();

        dragging = true;
        moved = false;
        startX = point.x;
        startY = point.y;
        originX = rect.left;
        originY = rect.top;
        rumi.classList.add('is-dragging');
    };

    const move = (event) => {
        if (!dragging) {
            return;
        }

        const point = pointerPosition(event);
        const deltaX = point.x - startX;
        const deltaY = point.y - startY;

        if (Math.abs(deltaX) + Math.abs(deltaY) > 5) {
            moved = true;
        }

        const x = clamp(originX + deltaX, 8, window.innerWidth - rumi.offsetWidth - 8);
        const y = clamp(originY + deltaY, 8, window.innerHeight - rumi.offsetHeight - 8);

        rumi.style.left = `${x}px`;
        rumi.style.top = `${y}px`;
        rumi.style.right = 'auto';
        rumi.style.bottom = 'auto';
        rumi.classList.add('is-custom-position');
        event.preventDefault();
    };

    const end = (event) => {
        if (!dragging) {
            return;
        }

        dragging = false;
        rumi.classList.remove('is-dragging');

        const rect = rumi.getBoundingClientRect();
        localStorage.setItem('rumi-position', JSON.stringify({ x: rect.left, y: rect.top }));

        if (moved) {
            suppressNextClick = true;
            event.preventDefault();
            event.stopPropagation();
        }
    };

    handle.addEventListener(
        'click',
        (event) => {
            if (!suppressNextClick) {
                return;
            }

            suppressNextClick = false;
            event.preventDefault();
            event.stopImmediatePropagation();
        },
        true
    );

    handle.addEventListener('mousedown', start);
    handle.addEventListener('touchstart', start, { passive: false });
    window.addEventListener('mousemove', move, { passive: false });
    window.addEventListener('touchmove', move, { passive: false });
    window.addEventListener('mouseup', end, true);
    window.addEventListener('touchend', end, true);
    window.addEventListener('resize', applySavedRumiPosition);
};

document.addEventListener('livewire:init', () => {
    window.Livewire?.on('rumi-click', ({ selector }) => {
        if (!selector) {
            return;
        }

        clickWhenReady(selector);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    runQueryAction();
    initRumiDrag();
});
document.addEventListener('livewire:navigated', () => {
    runQueryAction();
    initRumiDrag();
});
