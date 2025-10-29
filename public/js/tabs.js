(function () {
    'use strict';

    function setupTab(container) {
        const buttons = container.querySelectorAll('.tab-button');
        const sections = container.querySelectorAll('.tab-content');

        if (!buttons.length || !sections.length) {
            return;
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-tab');

                buttons.forEach((candidate) => {
                    candidate.classList.toggle('active', candidate === button);
                });

                sections.forEach((section) => {
                    const shouldShow = section.id === `tab-${target}`;
                    section.classList.toggle('active', shouldShow);
                });
            });
        });
    }

    function bootstrap() {
        const containers = document.querySelectorAll('.tab-container');
        containers.forEach(setupTab);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
})();
