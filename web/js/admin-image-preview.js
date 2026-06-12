(function () {
    'use strict';

    document.querySelectorAll('[data-admin-image-preview]').forEach(function (wrap) {
        var root = wrap.closest('form') || document;
        var input = wrap.parentElement.querySelector('[data-preview-input]');
        var preview = wrap.querySelector('[data-preview-img]');
        var placeholder = wrap.querySelector('[data-preview-placeholder]');

        if (!input || !preview) {
            return;
        }

        function showPreview(src) {
            preview.src = src;
            preview.classList.remove('d-none');
            if (placeholder) {
                placeholder.classList.add('d-none');
            }
        }

        preview.addEventListener('error', function () {
            preview.removeAttribute('src');
            preview.classList.add('d-none');
            if (placeholder) {
                placeholder.classList.remove('d-none');
            }
        });

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                return;
            }

            if (!file.type || file.type.indexOf('image/') !== 0) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                if (typeof event.target.result === 'string') {
                    showPreview(event.target.result);
                }
            };
            reader.readAsDataURL(file);
        });
    });
})();
