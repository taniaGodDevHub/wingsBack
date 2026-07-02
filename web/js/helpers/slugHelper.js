(function (global) {
    'use strict';

    const CYRILLIC_MAP = {
        а: 'a',
        б: 'b',
        в: 'v',
        г: 'g',
        д: 'd',
        е: 'e',
        ё: 'e',
        ж: 'zh',
        з: 'z',
        и: 'i',
        й: 'y',
        к: 'k',
        л: 'l',
        м: 'm',
        н: 'n',
        о: 'o',
        п: 'p',
        р: 'r',
        с: 's',
        т: 't',
        у: 'u',
        ф: 'f',
        х: 'h',
        ц: 'ts',
        ч: 'ch',
        ш: 'sh',
        щ: 'sch',
        ъ: '',
        ы: 'y',
        ь: '',
        э: 'e',
        ю: 'yu',
        я: 'ya',
    };

    function transliterate(text) {
        return Array.from(text).map(function (char) {
            const lower = char.toLowerCase();
            if (Object.prototype.hasOwnProperty.call(CYRILLIC_MAP, lower)) {
                return CYRILLIC_MAP[lower];
            }

            return char;
        }).join('');
    }

    function slugify(text) {
        return transliterate(text)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function bindAutoSlug(nameInput, slugInput) {
        if (!nameInput || !slugInput) {
            return;
        }

        let slugManual = false;
        let updatingSlug = false;

        nameInput.addEventListener('input', function () {
            if (slugManual) {
                return;
            }

            updatingSlug = true;
            slugInput.value = slugify(nameInput.value);
            updatingSlug = false;
        });

        slugInput.addEventListener('input', function () {
            if (updatingSlug) {
                return;
            }

            slugManual = true;
        });
    }

    function initContainer(container) {
        const nameInput = container.querySelector('input[name$="[name]"], input[name$="[title]"]');
        const slugInput = container.querySelector('input[name$="[slug]"]');
        bindAutoSlug(nameInput, slugInput);
    }

    function initAll(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-admin-slug]').forEach(initContainer);
    }

    global.WingsSlug = {
        slugify: slugify,
        bindAutoSlug: bindAutoSlug,
        initAll: initAll,
    };
})(window);
