(function () {
    'use strict';

    const carouselWrapSelector = '#product-images-carousel-wrap';

    document.querySelectorAll('.product-images-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const wrap = document.querySelector(carouselWrapSelector);
            if (!wrap) {
                return;
            }
            const manager = new ProductImagesManager(wrap);
            manager.setPendingFiles(Array.from(input.files));
            manager.render();
            syncInputFromPending(input, manager.getPendingFiles());
        });
    });

    initDeleteAjax();
    initUploadForm();
    document.addEventListener('DOMContentLoaded', initCarouselControls);

    function initCarouselControls() {
        document.querySelectorAll(carouselWrapSelector).forEach(function (wrap) {
            const manager = new ProductImagesManager(wrap);
            manager.updateControlsVisibility();
            const carouselEl = document.getElementById(manager.carouselId);
            if (carouselEl && typeof bootstrap !== 'undefined') {
                const items = carouselEl.querySelectorAll('.carousel-inner .carousel-item');
                if (items.length > 0) {
                    bootstrap.Carousel.getOrCreateInstance(carouselEl, { ride: false });
                }
            }
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

    function buildDeletePostParams(redirectAction, productId, imageId) {
        const csrf = getCsrfPair();
        const params = new URLSearchParams();
        if (csrf) {
            params.append(csrf.name, csrf.value);
        }
        params.append('redirect', redirectAction || 'update');
        if (productId) {
            params.append('productId', String(productId));
        }
        if (imageId) {
            params.append('imageId', String(imageId));
        }
        return params;
    }

    function resolveDeleteIds(trigger) {
        const slide = trigger.closest('[data-image-id]');
        const imageId = slide ? slide.getAttribute('data-image-id') : '';
        const wrap = document.querySelector(carouselWrapSelector);
        const productId = wrap ? wrap.dataset.productId : '';
        let parsedImageId = imageId;
        let parsedProductId = productId;
        const deleteUrl = trigger.getAttribute('data-delete-url') || trigger.href || '';
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

    function applyCarouselResponse(data) {
        if (data.carouselHtml) {
            replaceCarousel(data.carouselHtml);
            initCarouselControls();
        }
    }

    function getDefaultErrorMessage() {
        const wrap = document.querySelector(carouselWrapSelector);
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
            const deleteUrl = trigger.getAttribute('data-delete-url') || trigger.href || '';
            if (!deleteUrl) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const wrapForConfirm = document.querySelector(carouselWrapSelector);
            const confirmMessage = trigger.getAttribute('data-confirm')
                || (wrapForConfirm ? wrapForConfirm.dataset.labelConfirm : '');
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            const wrap = document.querySelector(carouselWrapSelector);
            const redirectAction = wrap ? wrap.dataset.redirectAction || 'update' : 'update';
            const ids = resolveDeleteIds(trigger);
            if (!ids.imageId || !ids.productId || !ids.deleteUrl) {
                showFlash('danger', getDefaultErrorMessage());
                return;
            }
            const params = buildDeletePostParams(redirectAction, ids.productId, ids.imageId);
            const csrf = getCsrfPair();
            if (!csrf) {
                showFlash('danger', getDefaultErrorMessage());
                return;
            }

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

                    applyCarouselResponse(data);

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
            const wrap = document.querySelector(carouselWrapSelector);
            if (!wrap) {
                return;
            }
            const manager = new ProductImagesManager(wrap);
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
                        replaceCarousel(data.carouselHtml);
                        initCarouselControls();
                    }
                    fileInput.value = '';
                    const wrap = document.querySelector(carouselWrapSelector);
                    if (wrap) {
                        const manager = new ProductImagesManager(wrap);
                        manager.setPendingFiles([]);
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

    function replaceCarousel(html) {
        const wrap = document.querySelector(carouselWrapSelector);
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
        this.carouselId = wrap.dataset.carouselId || 'product-images-carousel';
        this.carouselEl = document.getElementById(this.carouselId);
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
            prev: wrap.dataset.labelPrev || 'Previous',
            next: wrap.dataset.labelNext || 'Next',
            photo: wrap.dataset.labelPhoto || 'Photo {n}',
        };
        this.allowServerDelete = wrap.dataset.allowServerDelete === '1';
        this.ajaxDelete = wrap.dataset.ajaxDelete === '1';
        this.productId = wrap.dataset.productId || '';
        this.redirectAction = wrap.dataset.redirectAction || 'update';
    }

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
        if (!this.carouselEl) {
            return;
        }

        const inner = this.carouselEl.querySelector('.carousel-inner');
        const indicators = this.carouselEl.querySelector('.carousel-indicators');
        const slides = [];
        let slideIndex = 0;

        const self = this;
        this.serverImages.forEach(function (image) {
            slides.push(self.buildServerSlide(image, slideIndex === 0, slideIndex));
            slideIndex += 1;
        });

        this.pendingUrls.forEach(function (url, pendingIndex) {
            slides.push(self.buildPendingSlide(url, pendingIndex, slideIndex === 0, slideIndex));
            slideIndex += 1;
        });

        if (slides.length === 0) {
            inner.innerHTML = '<div class="carousel-item active product-image-carousel__empty-item">'
                + '<div class="product-image-carousel__frame p-2 text-center">'
                + '<span class="text-muted">' + escapeHtml(self.labels.empty) + '</span>'
                + '</div></div>';
            indicators.innerHTML = '';
        } else {
            inner.innerHTML = slides.join('');
            indicators.innerHTML = '';
            if (slides.length > 1) {
                for (let i = 0; i < slides.length; i += 1) {
                    const label = self.labels.photo.replace('{n}', String(i + 1));
                    indicators.innerHTML += '<button type="button" data-bs-target="#' + self.carouselId + '" data-bs-slide-to="' + i + '"'
                        + (i === 0 ? ' class="active" aria-current="true"' : '')
                        + ' aria-label="' + escapeHtml(label) + '"></button>';
                }
            }
        }

        this.updateControlsVisibility();
        if (slides.length > 0 && typeof bootstrap !== 'undefined') {
            bootstrap.Carousel.getOrCreateInstance(this.carouselEl, { ride: false });
        }
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

    ProductImagesManager.prototype.buildServerSlide = function (image, isActive, index) {
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
        return '<div class="carousel-item' + (isActive ? ' active' : '') + '" data-server-image="1" data-image-id="' + image.id + '">'
            + '<div class="product-image-carousel__frame p-2">'
            + deleteHtml
            + '<img src="' + escapeHtml(image.url) + '" class="product-image-carousel__img" alt="">'
            + '</div></div>';
    };

    ProductImagesManager.prototype.buildPendingSlide = function (url, pendingIndex, isActive, index) {
        return '<div class="carousel-item' + (isActive ? ' active' : '') + '" data-pending-image="1">'
            + '<div class="product-image-carousel__frame p-2">'
            + '<button type="button" class="btn btn-sm btn-danger product-image-delete-btn product-image-delete--pending"'
            + ' data-pending-index="' + pendingIndex + '"'
            + deleteButtonAttrs(this.labels.delete) + '>'
            + deleteIconSvg() + '</button>'
            + '<img src="' + escapeHtml(url) + '" class="product-image-carousel__img" alt="">'
            + '</div></div>';
    };

    ProductImagesManager.prototype.updateControlsVisibility = function () {
        if (!this.carouselEl) {
            return;
        }
        const count = this.carouselEl.querySelectorAll('.carousel-inner .carousel-item:not(.product-image-carousel__empty-item)').length
            || this.carouselEl.querySelectorAll('.carousel-inner .carousel-item').length;
        const realCount = this.serverImages.length + this.pendingFiles.length;
        const showControls = realCount > 1;
        this.carouselEl.querySelectorAll('.carousel-control-prev, .carousel-control-next').forEach(function (btn) {
            btn.classList.toggle('d-none', !showControls);
        });
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
