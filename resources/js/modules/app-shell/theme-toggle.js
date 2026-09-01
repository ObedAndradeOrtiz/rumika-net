const THEME_KEY = 'rumika-theme';

const currentTheme = () => document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';

const applyTheme = (theme) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = normalizedTheme;
    localStorage.setItem(THEME_KEY, normalizedTheme);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const isDark = normalizedTheme === 'dark';
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    });
};

const bootThemeToggles = () => {
    applyTheme(currentTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        if (button.dataset.themeReady === 'true') {
            return;
        }

        button.dataset.themeReady = 'true';
        button.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });
};

document.addEventListener('DOMContentLoaded', bootThemeToggles);
document.addEventListener('livewire:navigated', bootThemeToggles);
document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', bootThemeToggles);
});
