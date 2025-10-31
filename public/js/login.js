(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var form = document.querySelector('[data-auth-form]');
        var loading = document.querySelector('[data-auth-loading]');
        if (!form || !loading) {
            return;
        }

        form.addEventListener('submit', function () {
            loading.removeAttribute('hidden');
            var submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.setAttribute('disabled', 'disabled');
            }
        }, { once: false });
    });
})();
