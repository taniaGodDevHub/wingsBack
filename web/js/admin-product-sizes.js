(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const field = document.getElementById('product-size-field');
        const inputsContainer = document.getElementById('product-size-inputs');

        if (!field || !inputsContainer) {
            return;
        }

        const inputName = inputsContainer.querySelector('input')?.name
            || 'Product[sizeValuesInStock][]';

        function syncInputs() {
            inputsContainer.innerHTML = '';

            field.querySelectorAll('.product-size-btn--in-stock').forEach(function (button) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = inputName;
                input.value = button.dataset.size || '';
                inputsContainer.appendChild(input);
            });
        }

        field.addEventListener('click', function (event) {
            const button = event.target.closest('.product-size-btn');
            if (!button || !field.contains(button)) {
                return;
            }

            const inStock = button.classList.toggle('product-size-btn--in-stock');
            button.setAttribute('aria-pressed', inStock ? 'true' : 'false');
            syncInputs();
        });
    });
})();
