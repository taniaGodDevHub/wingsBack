(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const chart = document.getElementById('product-size-chart');
        if (!chart) {
            return;
        }

        chart.addEventListener('click', function (event) {
            const button = event.target.closest('.product-size-btn');
            if (!button || !chart.contains(button)) {
                return;
            }

            const row = button.closest('[data-size-row]');
            const stockInput = row ? row.querySelector('[data-size-stock-input]') : null;
            const inStock = button.classList.toggle('product-size-btn--in-stock');
            button.setAttribute('aria-pressed', inStock ? 'true' : 'false');

            if (stockInput) {
                stockInput.value = inStock ? '1' : '0';
            }
        });
    });
})();
