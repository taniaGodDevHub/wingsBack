/**
 * Persist Hyper light/dark theme in localStorage (guest-friendly).
 */
(function () {
    'use strict';

    var storageKey = 'wings-theme';

    function getStoredTheme() {
        try {
            var t = localStorage.getItem(storageKey);
            return t === 'dark' || t === 'light' ? t : null;
        } catch (e) {
            return null;
        }
    }

    function setStoredTheme(theme) {
        try {
            localStorage.setItem(storageKey, theme);
        } catch (e) {}
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        if (window.config) {
            window.config.theme = theme;
            try {
                sessionStorage.setItem('__HYPER_CONFIG__', JSON.stringify(window.config));
            } catch (e) {}
        }
    }

    var stored = getStoredTheme();
    if (stored) {
        applyTheme(stored);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('light-dark-mode');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            window.setTimeout(function () {
                var theme = document.documentElement.getAttribute('data-bs-theme');
                if (theme === 'dark' || theme === 'light') {
                    setStoredTheme(theme);
                }
            }, 0);
        });
    });
})();
