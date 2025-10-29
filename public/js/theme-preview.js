(function () {
    'use strict';

    const form = document.querySelector('[data-theme-form]');
    if (!form) {
        return;
    }

    const body = document.body;
    const themeOptions = ['light', 'dark'];
    const paletteOptions = ['blue', 'emerald', 'violet', 'amber', 'rose'];

    function sanitize(value, fallback) {
        return typeof value === 'string' && value.trim() !== '' ? value.trim().toLowerCase() : fallback;
    }

    function applyAppearance(theme, palette) {
        const nextTheme = sanitize(theme, body.getAttribute('data-theme-mode') || 'light');
        const nextPalette = sanitize(palette, body.getAttribute('data-color-palette') || 'blue');

        themeOptions.forEach(function (option) {
            body.classList.remove('theme-' + option);
        });
        paletteOptions.forEach(function (option) {
            body.classList.remove('accent-' + option);
        });

        body.classList.add('theme-' + (themeOptions.includes(nextTheme) ? nextTheme : 'light'));
        body.classList.add('accent-' + (paletteOptions.includes(nextPalette) ? nextPalette : 'blue'));

        body.setAttribute('data-theme-mode', nextTheme);
        body.setAttribute('data-color-palette', nextPalette);
    }

    function currentSelection() {
        const themeInput = form.querySelector('input[name="theme_mode"]:checked');
        const paletteInput = form.querySelector('input[name="color_palette"]:checked');

        return {
            theme: themeInput ? themeInput.value : null,
            palette: paletteInput ? paletteInput.value : null,
        };
    }

    function handleChange() {
        const selection = currentSelection();
        applyAppearance(selection.theme, selection.palette);
    }

    form.addEventListener('change', handleChange);

    handleChange();
})();
