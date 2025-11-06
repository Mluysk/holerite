(function () {
    'use strict';

    const body = document.body;
    const themeForm = document.querySelector('[data-theme-mode-form]');
    const paletteForm = document.querySelector('[data-color-palette-form]');

    if (!themeForm && !paletteForm) {
        return;
    }

    const themeOptions = ['light', 'dark'];
    const paletteOptions = [
        'blue',
        'emerald',
        'violet',
        'amber',
        'rose',
        'black',
        'gray',
        'red',
        'dark-red',
        'pink',
        'yellow',
        'gold',
        'rgb',
        'light-blue',
        'dark-blue',
        'wine'
    ];

    function sanitize(value, fallback) {
        return typeof value === 'string' && value.trim() !== '' ? value.trim().toLowerCase() : fallback;
    }

    let currentTheme = sanitize(body.getAttribute('data-theme-mode'), 'light');
    let currentPalette = sanitize(body.getAttribute('data-color-palette'), 'dark-red');

    function applyAppearance(theme, palette) {
        const nextTheme = sanitize(theme, currentTheme);
        const nextPalette = sanitize(palette, currentPalette);

        themeOptions.forEach(function (option) {
            body.classList.remove('theme-' + option);
        });
        paletteOptions.forEach(function (option) {
            body.classList.remove('accent-' + option);
        });

        const appliedTheme = themeOptions.includes(nextTheme) ? nextTheme : 'light';
        const appliedPalette = paletteOptions.includes(nextPalette) ? nextPalette : 'dark-red';

        body.classList.add('theme-' + appliedTheme);
        body.classList.add('accent-' + appliedPalette);

        body.setAttribute('data-theme-mode', appliedTheme);
        body.setAttribute('data-color-palette', appliedPalette);

        currentTheme = appliedTheme;
        currentPalette = appliedPalette;
    }

    function handleThemeChange() {
        if (!themeForm) {
            return;
        }

        const input = themeForm.querySelector('input[name="theme_mode"]:checked');
        const value = input ? input.value : null;
        currentTheme = sanitize(value, currentTheme);
        applyAppearance(currentTheme, currentPalette);
    }

    function handlePaletteChange() {
        if (!paletteForm) {
            return;
        }

        const input = paletteForm.querySelector('input[name="color_palette"]:checked');
        const value = input ? input.value : null;
        currentPalette = sanitize(value, currentPalette);
        applyAppearance(currentTheme, currentPalette);
    }

    if (themeForm) {
        themeForm.addEventListener('change', handleThemeChange);
    }

    if (paletteForm) {
        paletteForm.addEventListener('change', handlePaletteChange);
    }

    applyAppearance(currentTheme, currentPalette);
})();
