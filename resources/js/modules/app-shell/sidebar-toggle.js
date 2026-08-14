const sidebarStorageKey = 'rumika:sidebar-collapsed';

const applySidebarState = (shell, collapsed) => {
    shell.classList.toggle('is-collapsed', collapsed);
    shell.querySelector('[data-sidebar-toggle]')?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
};

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('[data-rm-shell]');

    if (! shell) {
        return;
    }

    applySidebarState(shell, localStorage.getItem(sidebarStorageKey) === 'true');
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-sidebar-toggle]');

    if (! toggle) {
        const groupSummary = event.target.closest('.rm-menu-group > summary');

        if (! groupSummary) {
            return;
        }

        const shell = groupSummary.closest('[data-rm-shell]');

        if (! shell?.classList.contains('is-collapsed')) {
            return;
        }

        event.preventDefault();
        localStorage.setItem(sidebarStorageKey, 'false');
        applySidebarState(shell, false);
        groupSummary.parentElement?.setAttribute('open', '');

        return;
    }

    const shell = toggle.closest('[data-rm-shell]');

    if (! shell) {
        return;
    }

    const collapsed = ! shell.classList.contains('is-collapsed');
    localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false');
    applySidebarState(shell, collapsed);
});
