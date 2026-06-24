(function () {
    'use strict';

    function boot() {
        if (window.WingsSlug) {
            window.WingsSlug.initAll();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
