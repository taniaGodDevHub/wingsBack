(function () {
    'use strict';

    function normalizeHex(value) {
        var raw = String(value || '').trim();
        if (raw === '') {
            return null;
        }

        if (raw.charAt(0) !== '#') {
            raw = '#' + raw;
        }

        if (/^#[0-9A-Fa-f]{6}$/.test(raw)) {
            return raw.toUpperCase();
        }

        if (/^#[0-9A-Fa-f]{3}$/.test(raw)) {
            return (
                '#' +
                raw.charAt(1) + raw.charAt(1) +
                raw.charAt(2) + raw.charAt(2) +
                raw.charAt(3) + raw.charAt(3)
            ).toUpperCase();
        }

        return null;
    }

    function initHexColorField(wrap) {
        if (wrap.dataset.hexInitialized === '1') {
            return;
        }

        var picker = wrap.querySelector('.hex-color-field__picker');
        var text = wrap.querySelector('.hex-color-field__text');
        if (!picker || !text) {
            return;
        }

        wrap.dataset.hexInitialized = '1';

        var initial = normalizeHex(text.value) || '#000000';
        text.value = normalizeHex(text.value) || '';
        picker.value = initial.toLowerCase();

        picker.addEventListener('input', function () {
            text.value = picker.value.toUpperCase();
        });

        text.addEventListener('input', function () {
            var hex = normalizeHex(text.value);
            if (hex) {
                picker.value = hex.toLowerCase();
            }
        });

        text.addEventListener('blur', function () {
            var hex = normalizeHex(text.value);
            if (hex) {
                text.value = hex;
                picker.value = hex.toLowerCase();
            }
        });
    }

    function initAll() {
        document.querySelectorAll('.hex-color-field').forEach(initHexColorField);
    }

    document.addEventListener('DOMContentLoaded', initAll);
    window.initHexColorFields = initAll;
})();
