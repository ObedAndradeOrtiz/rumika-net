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

document.addEventListener('livewire:init', () => {
    window.Livewire?.on('rumi-click', ({ selector }) => {
        if (!selector) {
            return;
        }

        clickWhenReady(selector);
    });
});

document.addEventListener('DOMContentLoaded', runQueryAction);
document.addEventListener('livewire:navigated', runQueryAction);
