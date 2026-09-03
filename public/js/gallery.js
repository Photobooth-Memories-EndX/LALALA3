(function () {
    'use strict';

    var controller = null;
    var modalSnapshot = null;
    var modalCommitted = false;

    function page() {
        return document.querySelector('[data-gallery-page]');
    }

    function form() {
        return document.querySelector('[data-gallery-form]');
    }

    function results() {
        return document.querySelector('[data-gallery-results]');
    }

    function modal() {
        return document.querySelector('[data-gallery-modal]');
    }

    function modalInstance(element) {
        return element && window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(element)
            : null;
    }

    function snapshotModal(element) {
        return Array.prototype.map.call(element.querySelectorAll('input, select'), function (control) {
            return {
                control: control,
                checked: control.checked,
                value: control.value
            };
        });
    }

    function restoreSnapshot() {
        if (!modalSnapshot) return;
        modalSnapshot.forEach(function (item) {
            if (!item.control.isConnected) return;
            item.control.value = item.value;
            if ('checked' in item.control) item.control.checked = item.checked;
        });
    }

    function syncModalFromQuick() {
        var currentForm = form();
        if (!currentForm) return;
        var views = currentForm.querySelector('[data-gallery-views]');

        currentForm.querySelectorAll('[data-gallery-modal-views]').forEach(function (radio) {
            radio.checked = radio.value === (views ? views.value : '');
        });
    }

    function syncQuickFromModal() {
        var currentForm = form();
        if (!currentForm) return;
        var views = currentForm.querySelector('[data-gallery-views]');
        var checkedViews = currentForm.querySelector('[data-gallery-modal-views]:checked');
        var sort = currentForm.querySelector('[data-gallery-sort]');
        var checkedLikes = currentForm.querySelector('[name="likes"]:checked');

        if (views && checkedViews) {
            views.value = checkedViews.value;
            if (checkedViews.value && sort) sort.value = checkedViews.value;
            if (!checkedViews.value && sort && /_viewed$/.test(sort.value)) sort.value = 'latest';
        }
        if (checkedLikes && sort && checkedLikes.value) sort.value = checkedLikes.value;
        if (checkedLikes && !checkedLikes.value && sort && /_liked$/.test(sort.value)) sort.value = 'latest';
    }

    function validDateRange(currentForm) {
        var from = currentForm.querySelector('[name="date_from"]');
        var to = currentForm.querySelector('[name="date_to"]');
        var valid = !from || !to || !from.value || !to.value || to.value >= from.value;
        if (to) {
            to.classList.toggle('is-invalid', !valid);
            to.setAttribute('aria-invalid', valid ? 'false' : 'true');
        }
        return valid;
    }

    function buildUrl(currentForm) {
        var target = new URL(currentForm.action, window.location.origin);
        var data = new FormData(currentForm);
        var modalUploader = data.get('modal_uploader');
        if (modalUploader) data.set('uploader', modalUploader);
        data.delete('modal_views');
        data.delete('modal_uploader');
        data.delete('gallery_source');
        data.forEach(function (value, key) {
            if (String(value).trim() !== '' && !(key === 'sort' && value === 'latest')) {
                target.searchParams.append(key, value);
            }
        });
        return target;
    }

    function syncQuickFilter(item) {
        var currentForm = form();
        if (!currentForm) return;
        var filter = item.getAttribute('data-filter');
        var value = item.getAttribute('data-value') || '';
        var dropdown = item.closest('.dropdown');
        var button = dropdown ? dropdown.querySelector('.gallery-fast-filter-control') : null;
        var label = button ? button.querySelector('[data-gallery-filter-label]') : null;

        if (filter === 'month' || filter === 'year') {
            currentForm.querySelectorAll('[name="' + filter + '[]"]').forEach(function (input) {
                input.checked = value !== '' && input.value === value;
            });
        }

        if (filter === 'views') {
            var views = currentForm.querySelector('[data-gallery-views]');
            var sort = currentForm.querySelector('[data-gallery-sort]');
            if (views) views.value = value;
            if (sort && value) sort.value = value;
            if (sort && !value && /_viewed$/.test(sort.value)) sort.value = 'latest';
            currentForm.querySelectorAll('[name="likes"]').forEach(function (radio) {
                radio.checked = radio.value === '';
            });
            syncModalFromQuick();
        }

        if (dropdown) {
            dropdown.querySelectorAll('.gallery-quick-filter-item').forEach(function (option) {
                option.classList.toggle('active', option === item);
            });
        }
        if (button) button.classList.toggle('active', value !== '');
        if (label) label.textContent = item.textContent.trim();
    }

    function errorHtml() {
        return '<div class="gallery-error-state" role="alert">' +
            '<div class="gallery-error-icon"><i class="ti ti-wifi-off" aria-hidden="true"></i></div>' +
            '<h3 class="mt-3">Gallery could not be loaded</h3>' +
            '<p class="text-muted">Check your connection and try again.</p>' +
            '<button type="button" class="btn btn-outline-primary" data-gallery-retry>Retry</button>' +
        '</div>';
    }

    function replaceResponse(payload) {
        var resultRegion = results();
        var filterRegion = document.querySelector('[data-gallery-filter-region]');
        if (resultRegion) resultRegion.innerHTML = payload.html;
        if (filterRegion && payload.filters_html) filterRegion.outerHTML = payload.filters_html;
        document.dispatchEvent(new CustomEvent('gallery:updated'));
    }

    function loadUrl(target, options) {
        options = options || {};
        var resultRegion = results();
        if (!resultRegion) return Promise.resolve();

        if (controller) controller.abort();
        var requestController = new AbortController();
        controller = requestController;
        resultRegion.setAttribute('aria-busy', 'true');

        return fetch(target.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: requestController.signal
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (payload) {
            replaceResponse(payload);
            syncModalFromQuick();
            if (options.push !== false) history.pushState({}, '', target.toString());
            var refreshedResults = results();
            if (refreshedResults) {
                refreshedResults.setAttribute('aria-busy', 'false');
                refreshedResults.setAttribute('tabindex', '-1');
                if (options.focus !== false) refreshedResults.focus({ preventScroll: true });
            }
        }).catch(function (error) {
            if (error.name === 'AbortError') return;
            var currentResults = results();
            if (currentResults) {
                currentResults.setAttribute('aria-busy', 'false');
                currentResults.innerHTML = errorHtml();
            }
        }).finally(function () {
            if (controller === requestController) controller = null;
        });
    }

    function clearParameter(button) {
        var parameter = button.getAttribute('data-gallery-clear-param');
        var value = button.getAttribute('data-gallery-clear-value');
        var target = new URL(window.location.href);
        var arrayKey = parameter + '[]';

        if (['category', 'year', 'month'].indexOf(parameter) !== -1) {
            var remaining = [];
            Array.from(target.searchParams.entries()).forEach(function (entry) {
                var key = entry[0];
                if (key === parameter || key === arrayKey || key.indexOf(parameter + '[') === 0) {
                    if (entry[1] !== value) remaining.push(entry[1]);
                    target.searchParams.delete(key);
                }
            });
            remaining.forEach(function (item) { target.searchParams.append(arrayKey, item); });
        } else {
            target.searchParams.delete(parameter);
            if (parameter === 'likes' && /_liked$/.test(target.searchParams.get('sort') || '')) {
                target.searchParams.delete('sort');
            }
        }
        target.searchParams.delete('page');
        loadUrl(target);
    }

    document.addEventListener('show.bs.modal', function (event) {
        if (!event.target.matches('[data-gallery-modal]')) return;
        syncModalFromQuick();
        modalSnapshot = snapshotModal(event.target);
        modalCommitted = false;
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        if (!event.target.matches('[data-gallery-modal]')) return;
        if (!modalCommitted) restoreSnapshot();
        modalSnapshot = null;
        modalCommitted = false;
    });

    document.addEventListener('change', function (event) {
        var currentForm = form();
        if (!currentForm || !currentForm.contains(event.target)) return;

        if (event.target.matches('[data-gallery-views]')) {
            var sort = currentForm.querySelector('[data-gallery-sort]');
            if (event.target.value && sort) sort.value = event.target.value;
            if (!event.target.value && sort && /_viewed$/.test(sort.value)) sort.value = 'latest';
            syncModalFromQuick();
        }

        if (event.target.matches('[data-gallery-sort]')) {
            var views = currentForm.querySelector('[data-gallery-views]');
            if (views) views.value = /_viewed$/.test(event.target.value) ? event.target.value : '';
            currentForm.querySelectorAll('[name="likes"]').forEach(function (radio) {
                radio.checked = radio.value === (/_liked$/.test(event.target.value) ? event.target.value : '');
            });
            syncModalFromQuick();
        }
    });

    document.addEventListener('submit', function (event) {
        if (!event.target.matches('[data-gallery-form]')) return;
        if (!validDateRange(event.target) || !window.fetch || !window.URLSearchParams) {
            if (!validDateRange(event.target)) event.preventDefault();
            return;
        }
        event.preventDefault();
        var target = buildUrl(event.target);
        target.searchParams.delete('page');
        var openModal = modal();
        if (openModal && openModal.classList.contains('show')) modalInstance(openModal).hide();
        loadUrl(target);
    });

    function escapeGalleryHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // -------------------------------------------------------------------
    // AUTOCOMPLETE DISABLED: now handled exclusively by gallery-script.blade.php
    // Removed to prevent:
    //   1. Cross-triggering via [name="q"] matching #header-search-input
    //   2. searchResults.innerHTML overwrite destroying #gallery-autocomplete-list
    //      and #gallery-search-footer dual-container DOM structure
    // -------------------------------------------------------------------
    // var gallerySearchTimer = null;
    //
    // document.addEventListener('input', function (event) {
    //     if (!event.target.matches('#gallery-search-input, #gallery-search, [name="q"]')) return;
    //     var input = event.target;
    //     var searchResults = document.getElementById('gallery-search-results');
    //     var value = input.value.trim();
    //     clearTimeout(gallerySearchTimer);
    //     if (!searchResults) return;
    //     if (value.length < 1) {
    //         searchResults.style.display = 'none';
    //         searchResults.innerHTML = '';
    //         return;
    //     }
    //     gallerySearchTimer = setTimeout(function () {
    //         fetch('/gallery/search-autocomplete?q=' + encodeURIComponent(value), {
    //             headers: { 'X-Requested-With': 'XMLHttpRequest' }
    //         })
    //         .then(function (response) {
    //             if (!response.ok) throw new Error('HTTP ' + response.status);
    //             return response.json();
    //         })
    //         .then(function (data) {
    //             if (!data || data.length === 0) {
    //                 searchResults.innerHTML = '<div class="dropdown-item text-muted small py-2 px-3">No event galleries found</div>';
    //             } else {
    //                 var html = '';
    //                 data.forEach(function (item) {
    //                     var photoLabel = (item.photo_count || 0) + ' ' + (item.photo_count === 1 ? 'Photo' : 'Photos');
    //                     var subDetails = [];
    //                     subDetails.push(photoLabel);
    //                     if (item.category) subDetails.push(escapeGalleryHtml(item.category));
    //                     if (item.event_date) subDetails.push(escapeGalleryHtml(item.event_date));
    //                     html += '<a href="#" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle gallery-search-item" data-title="' + escapeGalleryHtml(item.title) + '">' +
    //                         '<div class="d-flex align-items-center justify-content-between mb-1">' +
    //                             '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeGalleryHtml(item.title) + '</span>' +
    //                             '<span class="badge bg-purple-lt text-purple small flex-shrink-0">GALLERY</span>' +
    //                         '</div>' +
    //                         '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.join(' &bull; ') + '</div>' +
    //                     '</a>';
    //                 });
    //                 searchResults.innerHTML = html;
    //             }
    //             searchResults.style.display = 'block';
    //         })
    //         .catch(function () {
    //             searchResults.style.display = 'none';
    //         });
    //     }, 250);
    // });
    //
    // document.addEventListener('focusin', function (event) {
    //     if (!event.target.matches('#gallery-search-input, #gallery-search, [name="q"]')) return;
    //     var searchResults = document.getElementById('gallery-search-results');
    //     if (searchResults && event.target.value.trim().length >= 1 && searchResults.children.length > 0) {
    //         searchResults.style.display = 'block';
    //     }
    // });
    //
    // document.addEventListener('keydown', function (event) {
    //     if (event.target.matches('#gallery-search-input, #gallery-search, [name="q"]') && event.key === 'Enter') {
    //         var searchResults = document.getElementById('gallery-search-results');
    //         if (searchResults) searchResults.style.display = 'none';
    //     }
    // });

    document.addEventListener('click', function (event) {
        var searchItem = event.target.closest('.gallery-search-item');
        if (searchItem) {
            event.preventDefault();
            var title = searchItem.getAttribute('data-title') || '';
            var searchInput = document.getElementById('gallery-search-input') || document.getElementById('gallery-search') || (form() ? form().querySelector('[name="q"]') : null);
            var searchResults = document.getElementById('gallery-search-results');
            if (searchInput) searchInput.value = title;
            if (searchResults) searchResults.style.display = 'none';
            var currentForm = form();
            if (currentForm) currentForm.requestSubmit();
            return;
        }

        var searchInputEl = document.getElementById('gallery-search-input') || document.getElementById('gallery-search');
        var searchResultsEl = document.getElementById('gallery-search-results');
        if (searchInputEl && searchResultsEl && !searchInputEl.contains(event.target) && !searchResultsEl.contains(event.target)) {
            searchResultsEl.style.display = 'none';
        }

        var quickFilter = event.target.closest('.gallery-quick-filter-item');
        if (quickFilter) {
            event.preventDefault();
            syncQuickFilter(quickFilter);
            return;
        }

        var apply = event.target.closest('[data-gallery-modal-apply]');
        if (apply) {
            event.preventDefault();
            var currentForm = form();
            if (!currentForm || !validDateRange(currentForm)) return;
            syncQuickFromModal();
            modalCommitted = true;
            currentForm.requestSubmit();
            return;
        }

        var resetModal = event.target.closest('[data-gallery-modal-reset]');
        if (resetModal) {
            event.preventDefault();
            var currentModal = modal();
            if (!currentModal) return;
            currentModal.querySelectorAll('input[type="checkbox"]').forEach(function (input) { input.checked = false; });
            currentModal.querySelectorAll('input[type="date"]').forEach(function (input) { input.value = ''; input.classList.remove('is-invalid'); });
            currentModal.querySelectorAll('[name="likes"], [data-gallery-modal-views]').forEach(function (input) { input.checked = input.value === ''; });
            var uploader = currentModal.querySelector('[data-gallery-modal-uploader]');
            if (uploader) uploader.value = '';
            return;
        }

        var chip = event.target.closest('[data-gallery-clear-param]');
        if (chip) {
            event.preventDefault();
            clearParameter(chip);
            return;
        }

        var reset = event.target.closest('[data-gallery-reset]');
        if (reset && page()) {
            event.preventDefault();
            var searchResultsReset = document.getElementById('gallery-search-results');
            if (searchResultsReset) {
                searchResultsReset.style.display = 'none';
                searchResultsReset.innerHTML = '';
            }
            loadUrl(new URL(reset.href, window.location.origin));
            return;
        }

        var pagination = event.target.closest('[data-gallery-results] .pagination a');
        if (pagination) {
            event.preventDefault();
            loadUrl(new URL(pagination.href, window.location.origin));
            return;
        }

        if (event.target.closest('[data-gallery-retry]')) {
            loadUrl(new URL(window.location.href), { push: false });
        }
    });

    document.addEventListener('error', function (event) {
        if (!event.target.matches('[data-gallery-image]')) return;
        event.target.hidden = true;
    }, true);

    window.addEventListener('popstate', function () {
        if (page()) loadUrl(new URL(window.location.href), { push: false, focus: false });
    });

    syncModalFromQuick();
}());
