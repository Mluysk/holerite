(function () {
    'use strict';

    function handleBackupForms() {
        var forms = document.querySelectorAll('form[data-backup-form]');

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === 'true') {
                    return;
                }

                var passwordInput = form.querySelector('input[name="restore_password"]');

                if (!passwordInput) {
                    return;
                }

                var passwordValue = passwordInput.value.trim();

                if (passwordValue !== '') {
                    form.dataset.submitting = 'true';
                    return;
                }

                event.preventDefault();

                var resource = form.getAttribute('data-backup-type') || 'backup';
                var message = 'Digite a senha de administrador para restaurar ' + resource + ':';
                var promptValue = window.prompt(message, '');

                if (promptValue === null) {
                    return;
                }

                promptValue = promptValue.trim();

                if (promptValue === '') {
                    passwordInput.focus();
                    return;
                }

                passwordInput.value = promptValue;
                form.dataset.submitting = 'true';
                form.submit();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleBackupForms);
    } else {
        handleBackupForms();
    }
})();
