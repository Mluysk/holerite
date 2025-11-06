(function () {
    const NAV_BREAKPOINT = 900;

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    onReady(() => {
        const nav = document.querySelector('[data-nav]');
        const toggle = document.querySelector('[data-nav-toggle]');
        if (!nav) {
            return;
        }

        const syncState = () => {
            const isCompact = window.innerWidth <= NAV_BREAKPOINT;
            if (isCompact) {
                nav.classList.add('is-collapsible');
                const userExpanded = nav.dataset.userExpanded === 'true';
                if (!userExpanded) {
                    nav.classList.remove('is-open');
                }
                if (toggle) {
                    toggle.classList.add('is-visible');
                    toggle.removeAttribute('hidden');
                    toggle.setAttribute('aria-expanded', userExpanded ? 'true' : 'false');
                }
            } else {
                nav.classList.remove('is-collapsible');
                nav.classList.add('is-open');
                nav.dataset.userExpanded = 'false';
                if (toggle) {
                    toggle.classList.remove('is-visible');
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.setAttribute('hidden', 'hidden');
                }
            }
        };

        syncState();
        window.addEventListener('resize', syncState, { passive: true });

        if (toggle) {
            toggle.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('is-open');
                nav.dataset.userExpanded = isOpen ? 'true' : 'false';
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }
    });
})();
