(function () {
    'use strict';

    function getContainer() {
        var container = document.querySelector('.app-flash-toasts');
        if (!container) {
            container = document.createElement('div');
            container.className = 'app-flash-toasts position-fixed bottom-0 end-0 p-3';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }

        return container;
    }

    function mapType(type) {
        if (type === 'error' || type === 'danger') {
            return 'danger';
        }

        return type || 'info';
    }

    window.WingsFlash = {
        show: function (type, message) {
            if (!message) {
                return;
            }

            var alertClass = 'alert-' + mapType(type);
            var alertEl = document.createElement('div');
            alertEl.className = 'alert ' + alertClass + ' alert-dismissible fade show shadow-sm mb-2';
            alertEl.setAttribute('role', 'alert');

            var body = document.createElement('div');
            body.textContent = message;
            alertEl.appendChild(body);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            closeBtn.setAttribute('aria-label', 'Close');
            alertEl.appendChild(closeBtn);

            getContainer().appendChild(alertEl);

            if (window.bootstrap && window.bootstrap.Alert) {
                window.setTimeout(function () {
                    window.bootstrap.Alert.getOrCreateInstance(alertEl).close();
                }, 6000);
            }
        },
    };

    function initExisting() {
        document.querySelectorAll('.app-flash-toasts .alert').forEach(function (alertEl) {
            if (window.bootstrap && window.bootstrap.Alert) {
                window.setTimeout(function () {
                    window.bootstrap.Alert.getOrCreateInstance(alertEl).close();
                }, 6000);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExisting);
    } else {
        initExisting();
    }
})();
