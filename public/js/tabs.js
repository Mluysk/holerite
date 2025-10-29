(function () {
    'use strict';

    function setupTab(container) {
        const buttons = container.querySelectorAll('.tab-button');
        const sections = container.querySelectorAll('.tab-content');

        if (!buttons.length || !sections.length) {
            return;
        }

        function showTab(target) {
            if (!target) {
                return;
            }

            buttons.forEach((candidate) => {
                const candidateTarget = candidate.getAttribute('data-tab');
                candidate.classList.toggle('active', candidateTarget === target);
            });

            sections.forEach((section) => {
                const shouldShow = section.id === `tab-${target}`;
                section.classList.toggle('active', shouldShow);
            });
        }

        const configuredDefault = container.getAttribute('data-default-tab');
        const activeButton = container.querySelector('.tab-button.active');
        const fallbackTab = buttons[0].getAttribute('data-tab');
        const initialTab = configuredDefault || (activeButton ? activeButton.getAttribute('data-tab') : null) || fallbackTab;

        showTab(initialTab);

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-tab');
                showTab(target);
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
