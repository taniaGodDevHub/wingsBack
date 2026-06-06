(function () {
    'use strict';

    const UNIT_PERCENT = 'percent';

    document.addEventListener('DOMContentLoaded', function () {
        const inputEl = document.getElementById('product-blago_input');
        const unitEl = document.getElementById('product-blago_unit');
        const previewEl = document.getElementById('product-blago-preview');

        if (!inputEl || !unitEl || !previewEl) {
            return;
        }

        const priceId = previewEl.dataset.priceField || 'product-price';
        const priceEl = document.getElementById(priceId);

        function formatAmount(value) {
            return value.toLocaleString('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
        }

        function updatePreview() {
            const unit = unitEl.value;
            const input = parseFloat(inputEl.value);
            const price = priceEl ? parseFloat(priceEl.value) : NaN;

            if (unit !== UNIT_PERCENT || Number.isNaN(input) || input <= 0) {
                previewEl.textContent = '';
                previewEl.classList.add('d-none');
                return;
            }

            if (Number.isNaN(price) || price <= 0) {
                previewEl.textContent = '';
                previewEl.classList.add('d-none');
                return;
            }

            const amount = Math.round(price * input) / 100;
            previewEl.textContent = '= ' + formatAmount(amount) + ' ₽';
            previewEl.classList.remove('d-none');
        }

        function syncPercentConstraints() {
            if (unitEl.value === UNIT_PERCENT) {
                inputEl.setAttribute('max', '100');
                inputEl.setAttribute('step', '0.01');
                return;
            }

            inputEl.removeAttribute('max');
            inputEl.setAttribute('step', '0.01');
        }

        inputEl.addEventListener('input', updatePreview);
        unitEl.addEventListener('change', function () {
            syncPercentConstraints();
            updatePreview();
        });

        if (priceEl) {
            priceEl.addEventListener('input', updatePreview);
        }

        syncPercentConstraints();
        updatePreview();
    });
})();
