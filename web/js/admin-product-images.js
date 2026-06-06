(function () {
    'use strict';

    const galleryWrapSelector = '#product-images-carousel-wrap';

    function getGalleryManager(wrap) {
        if (!wrap._productImagesManager) {
            wrap._productImagesManager = new ProductImagesManager(wrap);
        }
        return wrap._productImagesManager;
    }

    document.querySelectorAll('.product-images-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const wrap = document.querySelector(galleryWrapSelector);
            if (!wrap) {
                return;
            }
            const manager = getGalleryManager(wrap);
            manager.syncServerImagesFromDom();
            manager.setPendingFiles(Array.from(input.files));
            manager.render();
            syncInputFromPending(input, manager.getPendingFiles());
        });
    });

    initDeleteAjax();
    initUploadForm();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGallery);
    } else {
        initGallery();
    }

    function initDeleteInteractionGuards() {
        document.addEventListener('mousedown', function (event) {
            if (event.target.closest('.product-image-delete-btn')) {
                event.stopPropagation();
            }
        }, true);

        document.addEventListener('dragstart', function (event) {
            if (event.target.closest('.product-image-delete-btn')) {
                event.preventDefault();
            }
        }, true);
    }

    initDeleteInteractionGuards();

    function initGallery() {
        document.querySelectorAll(galleryWrapSelector).forEach(function (wrap) {
            getGalleryManager(wrap).bindSortable();
        });
    }

    function getCsrfPair() {
        const metaParam = document.querySelector('meta[name="csrf-param"]');
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaParam && metaToken) {
            return {
                name: metaParam.getAttribute('content'),
                value: metaToken.getAttribute('content'),
            };
        }
        const csrfInput = document.querySelector('input[name="_csrf"]');
        if (csrfInput) {
            return { name: '_csrf', value: csrfInput.value };
        }
        return null;
    }

    function buildPostParams(extra) {
        const csrf = getCsrfPair();
        const params = new URLSearchParams();
        if (csrf) {
            params.append(csrf.name, csrf.value);
        }
        Object.keys(extra).forEach(function (key) {
            const value = extra[key];
            if (Array.isArray(value)) {
                value.forEach(function (item) {
                    params.append(key + '[]', String(item));
                });
                return;
            }
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, String(value));
            }
        });
        return params;
    }

    function resolveDeleteIds(trigger) {
        const item = trigger.closest('[data-image-id]');
        const imageId = item ? item.getAttribute('data-image-id') : '';
        const wrap = document.querySelector(galleryWrapSelector);
        const productId = wrap ? wrap.dataset.productId : '';
        let deleteUrl = trigger.getAttribute('data-delete-url') || trigger.href || '';

        if ((!deleteUrl || deleteUrl === '#') && wrap && imageId) {
            const pattern = wrap.dataset.deleteUrlPattern || '';
            if (pattern) {
                deleteUrl = pattern.replace('__IMAGE_ID__', String(imageId));
            }
        }

        let parsedImageId = imageId;
        let parsedProductId = productId;
        if (deleteUrl) {
            try {
                const url = new URL(deleteUrl, window.location.origin);
                if (!parsedImageId) {
                    parsedImageId = url.searchParams.get('imageId') || '';
                }
                if (!parsedProductId) {
                    parsedProductId = url.searchParams.get('productId')
                        || url.searchParams.get('id')
                        || '';
                }
            } catch (e) {
                // keep attributes from DOM
            }
        }

        return {
            imageId: parsedImageId,
            productId: parsedProductId,
            deleteUrl: deleteUrl,
        };
    }

    function showFlash(type, message) {
        const container = document.querySelector('main .container');
        if (!container) {
            return;
        }

        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertEl = document.createElement('div');
        alertEl.className = 'alert ' + alertClass + ' alert-dismissible fade show';
        alertEl.setAttribute('role', 'alert');

        const body = document.createElement('span');
        body.textContent = message;
        alertEl.appendChild(body);

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('data-bs-dismiss', 'alert');
        closeBtn.setAttribute('aria-label', 'Close');
        alertEl.appendChild(closeBtn);

        const existingAlert = container.querySelector('.alert');
        if (existingAlert) {
            existingAlert.replaceWith(alertEl);
            return;
        }

        const breadcrumb = container.querySelector('.breadcrumb');
        if (breadcrumb) {
            breadcrumb.insertAdjacentElement('afterend', alertEl);
            return;
        }

        container.prepend(alertEl);
    }

    function applyGalleryResponse(data) {
        if (data.carouselHtml) {
            replaceGallery(data.carouselHtml);
            initGallery();
        }
    }

    function getDefaultErrorMessage() {
        const wrap = document.querySelector(galleryWrapSelector);
        return wrap ? wrap.dataset.labelError || 'Something went wrong. Please try again.' : 'Something went wrong. Please try again.';
    }

    function parseJsonResponse(response) {
        return response.json().catch(function () {
            return null;
        });
    }

    function initDeleteAjax() {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('.product-image-delete:not(.product-image-delete--pending)');
            if (!trigger) {
                return;
            }

            const wrap = document.querySelector(galleryWrapSelector);
            if (wrap && wrap.dataset.ajaxDelete !== '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const confirmMessage = trigger.getAttribute('data-confirm')
                || (wrap ? wrap.dataset.labelConfirm : '');
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            const redirectAction = wrap ? wrap.dataset.redirectAction || 'update' : 'update';
            const ids = resolveDeleteIds(trigger);
            if (!ids.imageId || !ids.productId || !ids.deleteUrl) {
                showFlash('danger', getDefaultErrorMessage());
                return;
            }
            const params = buildPostParams({
                redirect: redirectAction,
                productId: ids.productId,
                imageId: ids.imageId,
            });

            trigger.classList.add('disabled');
            trigger.setAttribute('aria-disabled', 'true');

            fetch(ids.deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: params.toString(),
            })
                .then(parseJsonResponse)
                .then(function (data) {
                    if (!data) {
                        showFlash('danger', getDefaultErrorMessage());
                        return;
                    }

                    applyGalleryResponse(data);

                    if (data.success) {
                        if (data.message) {
                            showFlash('success', data.message);
                        }
                        return;
                    }

                    showFlash('danger', data.error || getDefaultErrorMessage());
                })
                .catch(function () {
                    showFlash('danger', getDefaultErrorMessage());
                })
                .finally(function () {
                    trigger.classList.remove('disabled');
                    trigger.removeAttribute('aria-disabled');
                });
        }, true);

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.product-image-delete--pending');
            if (!btn) {
                return;
            }
            event.preventDefault();
            const wrap = document.querySelector(galleryWrapSelector);
            if (!wrap) {
                return;
            }
            const manager = getGalleryManager(wrap);
            const index = parseInt(btn.getAttribute('data-pending-index'), 10);
            if (Number.isNaN(index)) {
                return;
            }
            const confirmMessage = wrap.dataset.labelConfirm;
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }
            manager.removePendingAt(index);
            manager.render();
            document.querySelectorAll('.product-images-file-input').forEach(function (input) {
                syncInputFromPending(input, manager.getPendingFiles());
            });
        });
    }

    function initUploadForm() {
        const uploadBlock = document.getElementById('product-images-upload-form');
        if (!uploadBlock) {
            return;
        }

        const fileInput = document.getElementById('product-images-input');
        const errorsBox = document.getElementById('product-images-upload-errors');
        const submitBtn = document.getElementById('product-images-upload-btn');
        if (!submitBtn) {
            return;
        }

        submitBtn.addEventListener('click', function () {
            if (!fileInput || fileInput.files.length === 0) {
                showErrors(errorsBox, [uploadBlock.dataset.emptyMessage || 'Select at least one file.']);
                return;
            }

            const formData = new FormData();
            const csrfParam = uploadBlock.dataset.csrfParam;
            const csrfToken = uploadBlock.dataset.csrfToken;
            if (csrfParam && csrfToken) {
                formData.append(csrfParam, csrfToken);
            }
            Array.from(fileInput.files).forEach(function (file) {
                formData.append('productImages[]', file);
            });

            submitBtn.disabled = true;
            hideErrors(errorsBox);

            fetch(uploadBlock.dataset.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        showErrors(errorsBox, data.errors || ['Upload failed.']);
                        return;
                    }
                    if (data.carouselHtml) {
                        replaceGallery(data.carouselHtml);
                        initGallery();
                    }
                    fileInput.value = '';
                    const wrap = document.querySelector(galleryWrapSelector);
                    if (wrap) {
                        getGalleryManager(wrap).setPendingFiles([]);
                    }
                })
                .catch(function () {
                    showErrors(errorsBox, ['Upload failed.']);
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });
    }

    function replaceGallery(html) {
        const wrap = document.querySelector(galleryWrapSelector);
        if (wrap) {
            wrap.outerHTML = html;
        }
    }

    function syncInputFromPending(input, files) {
        const dt = new DataTransfer();
        files.forEach(function (file) {
            dt.items.add(file);
        });
        input.files = dt.files;
    }

    function showErrors(errorsBox, messages) {
        if (!errorsBox) {
            return;
        }
        errorsBox.textContent = messages.join(' ');
        errorsBox.classList.remove('d-none');
    }

    function hideErrors(errorsBox) {
        if (!errorsBox) {
            return;
        }
        errorsBox.textContent = '';
        errorsBox.classList.add('d-none');
    }

    function ProductImagesManager(wrap) {
        this.wrap = wrap;
        this.galleryId = wrap.dataset.carouselId || 'product-images-carousel';
        this.galleryEl = document.getElementById(this.galleryId);
        this.emptyEl = wrap.querySelector('.product-image-gallery__empty');
        this.pendingFiles = [];
        this.pendingUrls = [];
        this.serverImages = [];
        try {
            this.serverImages = JSON.parse(wrap.dataset.serverImages || '[]');
        } catch (e) {
            this.serverImages = [];
        }
        this.labels = {
            delete: wrap.dataset.labelDelete || 'Delete',
            empty: wrap.dataset.labelEmpty || 'No photos yet.',
            main: wrap.dataset.labelMain || 'Main photo',
            drag: wrap.dataset.labelDrag || 'Drag to reorder',
            orderSaved: wrap.dataset.labelOrderSaved || 'Image order saved.',
        };
        this.allowServerDelete = wrap.dataset.allowServerDelete === '1';
        this.allowReorder = wrap.dataset.allowReorder === '1';
        this.productId = wrap.dataset.productId || '';
        this.redirectAction = wrap.dataset.redirectAction || 'update';
        this.reorderUrl = wrap.dataset.reorderUrl || '';
        this.draggedItem = null;
        this._sortableBound = false;
    }

    ProductImagesManager.prototype.syncServerImagesFromDom = function () {
        if (!this.galleryEl) {
            return;
        }

        const fromDom = Array.from(this.galleryEl.querySelectorAll('[data-server-image="1"]'))
            .map(function (item) {
                const imageEl = item.querySelector('.product-image-gallery__img');
                return {
                    id: parseInt(item.getAttribute('data-image-id'), 10),
                    url: imageEl ? imageEl.getAttribute('src') || '' : '',
                    sortOrder: 0,
                };
            })
            .filter(function (image) {
                return !Number.isNaN(image.id) && image.id > 0 && image.url !== '';
            });

        if (fromDom.length > 0) {
            this.serverImages = fromDom;
        }
    };

    ProductImagesManager.prototype.setPendingFiles = function (files) {
        this.revokePendingUrls();
        this.pendingFiles = files.slice();
        this.pendingUrls = this.pendingFiles.map(function (file) {
            return URL.createObjectURL(file);
        });
    };

    ProductImagesManager.prototype.getPendingFiles = function () {
        return this.pendingFiles.slice();
    };

    ProductImagesManager.prototype.removePendingAt = function (index) {
        if (this.pendingUrls[index]) {
            URL.revokeObjectURL(this.pendingUrls[index]);
        }
        this.pendingFiles.splice(index, 1);
        this.pendingUrls.splice(index, 1);
    };

    ProductImagesManager.prototype.revokePendingUrls = function () {
        this.pendingUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        this.pendingUrls = [];
    };

    ProductImagesManager.prototype.render = function () {
        if (!this.galleryEl) {
            return;
        }

        this.syncServerImagesFromDom();

        const items = [];
        const self = this;

        this.serverImages.forEach(function (image, index) {
            items.push(self.buildServerItem(image, index === 0));
        });

        this.pendingUrls.forEach(function (url, pendingIndex) {
            items.push(self.buildPendingItem(url, pendingIndex, items.length === 0));
        });

        if (items.length === 0) {
            this.galleryEl.innerHTML = '';
            this.galleryEl.classList.add('d-none');
            if (this.emptyEl) {
                this.emptyEl.classList.remove('d-none');
            }
            return;
        }

        this.galleryEl.innerHTML = items.join('');
        this.galleryEl.classList.remove('d-none');
        if (this.emptyEl) {
            this.emptyEl.classList.add('d-none');
        }

        this.bindSortable();
    };

    ProductImagesManager.prototype.bindSortable = function () {
        if (!this.galleryEl) {
            return;
        }

        const self = this;
        const canDrag = this.allowReorder || this.pendingUrls.length > 0;

        this.galleryEl.querySelectorAll('.product-image-gallery__item').forEach(function (item) {
            const isPending = item.hasAttribute('data-pending-image');
            item.setAttribute('draggable', canDrag && (self.allowReorder || isPending) ? 'true' : 'false');
        });

        if (this.galleryEl.dataset.sortableBound === '1') {
            return;
        }
        this.galleryEl.dataset.sortableBound = '1';
        this._sortableBound = true;

        this.galleryEl.addEventListener('dragstart', function (event) {
            const item = event.target.closest('.product-image-gallery__item');
            if (!item || !self.galleryEl.contains(item)) {
                return;
            }
            if (item.getAttribute('draggable') !== 'true') {
                event.preventDefault();
                return;
            }
            self.draggedItem = item;
            item.classList.add('product-image-gallery__item--dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.getAttribute('data-image-id') || item.getAttribute('data-pending-index') || '');
        });

        this.galleryEl.addEventListener('dragover', function (event) {
            if (!self.draggedItem) {
                return;
            }
            event.preventDefault();
            const target = event.target.closest('.product-image-gallery__item');
            if (!target || target === self.draggedItem || !self.galleryEl.contains(target)) {
                return;
            }

            const draggedIsPending = self.draggedItem.hasAttribute('data-pending-image');
            const targetIsPending = target.hasAttribute('data-pending-image');
            if (draggedIsPending !== targetIsPending) {
                return;
            }

            const rect = target.getBoundingClientRect();
            const insertBefore = event.clientX < rect.left + rect.width / 2;
            if (insertBefore) {
                target.parentNode.insertBefore(self.draggedItem, target);
            } else {
                target.parentNode.insertBefore(self.draggedItem, target.nextSibling);
            }
        });

        this.galleryEl.addEventListener('dragend', function () {
            if (!self.draggedItem) {
                return;
            }

            const draggedWasPending = self.draggedItem.hasAttribute('data-pending-image');
            self.draggedItem.classList.remove('product-image-gallery__item--dragging');
            self.draggedItem = null;
            self.updateMainBadges();

            if (draggedWasPending) {
                self.syncPendingOrderFromDom();
                document.querySelectorAll('.product-images-file-input').forEach(function (input) {
                    syncInputFromPending(input, self.getPendingFiles());
                });
                return;
            }

            if (self.allowReorder && self.reorderUrl) {
                self.persistServerOrder();
            }
        });
    };

    ProductImagesManager.prototype.updateMainBadges = function () {
        if (!this.galleryEl) {
            return;
        }
        this.galleryEl.querySelectorAll('.product-image-gallery__badge').forEach(function (badge) {
            badge.remove();
        });
        const firstItem = this.galleryEl.querySelector('.product-image-gallery__item');
        if (!firstItem) {
            return;
        }
        const badge = document.createElement('span');
        badge.className = 'product-image-gallery__badge';
        badge.textContent = this.labels.main;
        firstItem.prepend(badge);
    };

    ProductImagesManager.prototype.collectServerImageIds = function () {
        if (!this.galleryEl) {
            return [];
        }
        return Array.from(this.galleryEl.querySelectorAll('[data-server-image="1"]'))
            .map(function (item) {
                return parseInt(item.getAttribute('data-image-id'), 10);
            })
            .filter(function (id) {
                return !Number.isNaN(id) && id > 0;
            });
    };

    ProductImagesManager.prototype.persistServerOrder = function () {
        const imageIds = this.collectServerImageIds();
        if (imageIds.length === 0 || !this.reorderUrl) {
            return;
        }

        const manager = this;
        const params = buildPostParams({
            redirect: this.redirectAction,
            productId: this.productId,
            imageIds: imageIds,
        });

        fetch(this.reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: params.toString(),
        })
            .then(parseJsonResponse)
            .then(function (data) {
                if (!data) {
                    showFlash('danger', getDefaultErrorMessage());
                    return;
                }
                if (data.success) {
                    applyGalleryResponse(data);
                    showFlash('success', data.message || manager.labels.orderSaved);
                    return;
                }
                showFlash('danger', data.error || getDefaultErrorMessage());
            })
            .catch(function () {
                showFlash('danger', getDefaultErrorMessage());
            });
    };

    ProductImagesManager.prototype.syncPendingOrderFromDom = function () {
        if (!this.galleryEl) {
            return;
        }
        const orderedIndexes = Array.from(this.galleryEl.querySelectorAll('[data-pending-image="1"]'))
            .map(function (item) {
                return parseInt(item.getAttribute('data-pending-index'), 10);
            })
            .filter(function (index) {
                return !Number.isNaN(index);
            });

        if (orderedIndexes.length === 0) {
            return;
        }

        const files = orderedIndexes.map(function (index) {
            return this.pendingFiles[index];
        }, this).filter(Boolean);

        const urls = orderedIndexes.map(function (index) {
            return this.pendingUrls[index];
        }, this).filter(Boolean);

        if (files.length !== this.pendingFiles.length || urls.length !== this.pendingUrls.length) {
            return;
        }

        this.pendingFiles = files;
        this.pendingUrls = urls;
        this.render();
    };

    function deleteIconSvg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">'
            + '<path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6"/>'
            + '<path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>'
            + '</svg>';
    }

    function deleteButtonAttrs(label) {
        return ' title="' + escapeHtml(label) + '" aria-label="' + escapeHtml(label) + '"';
    }

    ProductImagesManager.prototype.buildServerItem = function (image, isMain) {
        let deleteHtml = '';
        if (this.allowServerDelete && this.productId) {
            const pattern = this.wrap.dataset.deleteUrlPattern || '';
            const deleteUrl = pattern.replace('__IMAGE_ID__', String(image.id));
            const deleteClass = 'btn btn-sm btn-danger product-image-delete-btn product-image-delete product-image-delete--ajax';
            deleteHtml = '<button type="button" class="' + deleteClass + '"'
                + ' data-delete-url="' + escapeHtml(deleteUrl) + '"'
                + ' data-confirm="' + escapeHtml(this.wrap.dataset.labelConfirm || '') + '"'
                + deleteButtonAttrs(this.labels.delete) + '>'
                + deleteIconSvg() + '</button>';
        }

        const badgeHtml = isMain
            ? '<span class="product-image-gallery__badge">' + escapeHtml(this.labels.main) + '</span>'
            : '';

        return '<div class="product-image-gallery__item" data-server-image="1" data-image-id="' + image.id + '" draggable="' + (this.allowReorder ? 'true' : 'false') + '">'
            + badgeHtml
            + '<span class="product-image-gallery__drag" title="' + escapeHtml(this.labels.drag) + '" aria-hidden="true">⋮⋮</span>'
            + deleteHtml
            + '<img src="' + escapeHtml(image.url) + '" class="product-image-gallery__img" alt="">'
            + '</div>';
    };

    ProductImagesManager.prototype.buildPendingItem = function (url, pendingIndex, isMain) {
        const badgeHtml = isMain
            ? '<span class="product-image-gallery__badge">' + escapeHtml(this.labels.main) + '</span>'
            : '';

        return '<div class="product-image-gallery__item" data-pending-image="1" data-pending-index="' + pendingIndex + '" draggable="true">'
            + badgeHtml
            + '<span class="product-image-gallery__drag" title="' + escapeHtml(this.labels.drag) + '" aria-hidden="true">⋮⋮</span>'
            + '<button type="button" class="btn btn-sm btn-danger product-image-delete-btn product-image-delete--pending"'
            + ' data-pending-index="' + pendingIndex + '"'
            + deleteButtonAttrs(this.labels.delete) + '>'
            + deleteIconSvg() + '</button>'
            + '<img src="' + escapeHtml(url) + '" class="product-image-gallery__img" alt="">'
            + '</div>';
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
