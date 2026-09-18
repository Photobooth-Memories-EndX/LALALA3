<link rel="stylesheet" href="{{ asset('css/certificate-hover-preview.css') }}">
<script src="{{ asset('js/certificate-hover-preview.js') }}"></script>
<script>
    function initLearningCertificate() {
        var tableId = '#certificateTable';
        var $table = $(tableId);
        var certificateTable = null;

        if ($table.length) {
            certificateTable = $.fn.DataTable.isDataTable(tableId)
                ? $table.DataTable()
                : $table.DataTable({
                    ordering: false,
                    paging: false,
                    searching: false,
                    info: false,
                    autoWidth: false,
                    dom: 't',
                    language: { emptyTable: 'No certificates available.' }
                });
        }

        function initCertificateHoverPreview() {
            if (typeof window.initCertificateHoverPreview === 'function') {
                window.initCertificateHoverPreview();
            } else if (typeof window.initCertificatePreviewModal === 'function') {
                window.initCertificatePreviewModal();
            }
        }

        // Reserved for future Certificate Expiration feature per advisor decision.
        var certificateDefaults = {
            certificate_filter_type: 'all',
            certificate_filter_year: 'all',
            certificate_filter_month: 'all',
            certificate_issuer: '',
            certificate_date_from: '',
            certificate_date_to: '',
            // certificate_expiration_status: 'all',
            certificate_sort: 'all',
            certificate_search: ''
        };
        var certificateAdvancedNames = [
            'certificate_issuer',
            'certificate_date_from',
            'certificate_date_to',
            // 'certificate_expiration_status',
            'certificate_sort'
        ];

        window._certificateFilterState = window._certificateFilterState || {};
        var certificateFilterState = window._certificateFilterState;

        function certificateParams() {
            return new URLSearchParams(window.location.search);
        }

        function replaceCertificateState(nextState) {
            Object.keys(certificateFilterState).forEach(function (key) {
                delete certificateFilterState[key];
            });
            Object.keys(certificateDefaults).forEach(function (key) {
                certificateFilterState[key] = Object.prototype.hasOwnProperty.call(nextState, key)
                    ? String(nextState[key])
                    : certificateDefaults[key];
            });
            return certificateFilterState;
        }

        function resolveCertificateMonthNumber(val) {
            if (!val || val === 'all') return null;
            var str = String(val).trim().toLowerCase();
            var n = parseInt(str, 10);
            if (!isNaN(n) && n >= 1 && n <= 12) return n;
            var months = {
                january: 1, jan: 1,
                february: 2, feb: 2,
                march: 3, mar: 3,
                april: 4, apr: 4,
                may: 5,
                june: 6, jun: 6,
                july: 7, jul: 7,
                august: 8, aug: 8,
                september: 9, sep: 9, sept: 9,
                october: 10, oct: 10,
                november: 11, nov: 11,
                december: 12, dec: 12
            };
            return months[str] || null;
        }

        function readCertificateStateFromUrl() {
            var params = certificateParams();
            var nextState = {};

            Object.keys(certificateDefaults).forEach(function (key) {
                nextState[key] = params.has(key)
                    ? String(params.get(key) || certificateDefaults[key])
                    : certificateDefaults[key];
            });

            if (!params.has('certificate_search')) {
                if (params.has('search')) {
                    nextState['certificate_search'] = String(params.get('search'));
                } else if (params.has('q')) {
                    nextState['certificate_search'] = String(params.get('q'));
                }
            }

            if (!params.has('certificate_filter_type') && params.has('type')) {
                nextState['certificate_filter_type'] = String(params.get('type')).toLowerCase();
            }

            if (!params.has('certificate_filter_year') && params.has('year')) {
                nextState['certificate_filter_year'] = String(params.get('year'));
            }

            if (!params.has('certificate_filter_month') && params.has('month')) {
                var m = resolveCertificateMonthNumber(params.get('month'));
                nextState['certificate_filter_month'] = m !== null ? String(m) : String(params.get('month'));
            }

            return replaceCertificateState(nextState);
        }

        function cleanCertificateParams(params) {
            var filterKeys = ['certificate_search', 'search', 'q', 'type', 'year', 'month', 'sort', 'status'];
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('certificate_') === 0 || filterKeys.indexOf(key) !== -1) {
                    params.delete(key);
                }
            });
            return params;
        }

        function reloadCertificate(params, resetPage) {
            if (resetPage !== false) {
                params.delete('certificate_page');
            }
            params.delete('certificate_export');
            params.set('learning_tab', 'certificate');
            var query = params.toString();
            $('#certificateMoreFiltersModal').modal('hide');
            window.location.assign(window.location.pathname + (query ? '?' + query : ''));
        }

        function setQuickValue($dropdown, value, label) {
            $dropdown.attr('data-selected-value', value);
            $dropdown.find('.certificate-quick-value').val(value);
            $dropdown.find('.certificate-quick-item').removeClass('active')
                .filter(function () {
                    return String($(this).data('value')) === String(value);
                }).addClass('active');
            $dropdown.find('.dropdown-toggle')
                .toggleClass('active', value !== 'all')
                .find('.certificate-filter-value').text(label);
        }

        function quickFilterLabel($dropdown, value) {
            var $item = $dropdown.find('.certificate-quick-item').filter(function () {
                return String($(this).data('value')) === String(value);
            }).first();

            return $item.length ? $.trim($item.text()) : 'All';
        }

        function restoreCertificateInputs() {
            $('#certificate-search-input, #certificateSearch').val(certificateFilterState.certificate_search || '');

            $('#certificateFilterBar [data-certificate-filter]').each(function () {
                var $dropdown = $(this);
                var name = String($dropdown.find('.certificate-quick-value').attr('name'));
                var value = certificateFilterState[name] || 'all';
                setQuickValue($dropdown, value, quickFilterLabel($dropdown, value));
            });

            $('#certificate_issuer').val(certificateFilterState.certificate_issuer || '');
            $('#certificate_date_from').val(certificateFilterState.certificate_date_from || '');
            $('#certificate_date_to').val(certificateFilterState.certificate_date_to || '');

            var sortVal = certificateFilterState.certificate_sort || 'all';
            $('#certificateSort').val(sortVal);
        }

        function pendingAdvancedCount() {
            var count = 0;
            if ($.trim($('#certificate_issuer').val()) !== '') count++;
            if ($.trim($('#certificate_date_from').val()) !== '') count++;
            if ($.trim($('#certificate_date_to').val()) !== '') count++;

            var sortVal = $('#certificateSort').val() || 'all';
            if (sortVal !== 'all') count++;

            return count;
        }

        function updateAdvancedCount() {
            var count = pendingAdvancedCount();

            $('#certificateMoreFiltersCount')
                .toggleClass('d-none', count === 0)
                .text(count || '');
        }

        function collectCertificateState() {
            var nextState = {};

            $('#certificateFilterBar .certificate-quick-value').each(function () {
                var name = String(this.name);
                nextState[name] = String($(this).val() || 'all');
            });

            nextState.certificate_issuer = $.trim($('#certificate_issuer').val() || $('[name="certificate_issuer"]').val() || '');
            nextState.certificate_date_from = $.trim($('#certificate_date_from').val() || $('[name="certificate_date_from"]').val() || '');
            nextState.certificate_date_to = $.trim($('#certificate_date_to').val() || $('[name="certificate_date_to"]').val() || '');
            nextState.certificate_sort = $('#certificateSort').val() || 'all';
            nextState.certificate_search = $.trim($('#certificate-search-input, #certificateSearch').val());

            return replaceCertificateState(nextState);
        }

        function buildCertificateParams(state) {
            var params = cleanCertificateParams(certificateParams());
            Object.keys(certificateDefaults).forEach(function (key) {
                var value = String(state[key] || '');
                if (value !== '' && value !== certificateDefaults[key]) {
                    params.set(key, value);
                }
            });
            return params;
        }

        function collectCertificateFilters() {
            return buildCertificateParams(collectCertificateState());
        }

        function applyCertificateFilters() {
            clearTimeout(window.learningCertificateSearchTimer);
            collectCertificateState();
            updateAdvancedCount();
            reloadCertificate(buildCertificateParams(certificateFilterState));
        }

        function escapeCertificateHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Live Autocomplete Search for Certificate Filter ──────────────────────
        function initCertificateAutocomplete() {
            var searchInput = document.getElementById('certificate-search-input') || document.getElementById('certificateSearch');
            var searchResults = document.getElementById('certificate-search-results');
            var debounceTimer = null;

            if (!searchInput || !searchResults) return;

            var listEl = searchResults.querySelector('.autocomplete-list-group') || searchResults;
            var footerEl = searchResults.querySelector('.search-footer');

            $(searchInput).off('.learningCertificateSearch').on('input.learningCertificateSearch', function () {
                var value = $.trim(this.value);
                clearTimeout(debounceTimer);

                if (value.length < 1) {
                    searchResults.style.display = 'none';
                    if (listEl) listEl.innerHTML = '';
                    if (footerEl) footerEl.style.display = 'none';
                    clearTimeout(window.learningCertificateSearchTimer);
                    window.learningCertificateSearchTimer = setTimeout(function () {
                        applyCertificateFilters();
                    }, 400);
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch('/user/certificate/search-autocomplete?q=' + encodeURIComponent(value), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        var html = '';
                        if (data && data.length > 0) {
                            data.forEach(function (item) {
                                var badgeClass = item.type === 'playlist' || item.type === 'trainingplan'
                                    ? 'bg-teal-lt text-teal'
                                    : (item.type === 'global' ? 'bg-indigo-lt text-indigo' : 'bg-primary-lt text-primary');
                                var typeLabel = item.type_label || (item.type ? escapeCertificateHtml(item.type.toUpperCase()) : 'CONTENT');

                                var subDetails = [];
                                if (item.certificate_number) subDetails.push('No: ' + escapeCertificateHtml(item.certificate_number));
                                if (item.credential_id) subDetails.push('ID: ' + escapeCertificateHtml(item.credential_id));
                                if (item.issuer) subDetails.push(escapeCertificateHtml(item.issuer));

                                html += '<a href="#" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle certificate-search-item" data-title="' + escapeCertificateHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeCertificateHtml(item.title) + '</span>' +
                                        '<span class="badge ' + badgeClass + ' small flex-shrink-0">' + escapeCertificateHtml(typeLabel) + '</span>' +
                                    '</div>' +
                                    (subDetails.length > 0 ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.join(' &bull; ') + '</div>' : '') +
                                '</a>';
                            });
                        }
                        if (listEl) listEl.innerHTML = html;

                        var keyword = $.trim(searchInput.value);
                        if (footerEl) {
                            if (data && data.length > 0) {
                                footerEl.innerHTML = `
                                    <a href="/my-learning?learning_tab=certificate&q=${encodeURIComponent(keyword)}" class="dropdown-item fw-semibold text-primary py-2 d-flex align-items-center justify-content-center gap-2">
                                        <i class="ti ti-search"></i>
                                        <span>See all results for "${keyword}"</span>
                                    </a>
                                `;
                                footerEl.style.display = 'block';
                            } else {
                                footerEl.innerHTML = '<div class="dropdown-item text-muted disabled py-2">No items found</div>';
                                footerEl.style.display = 'block';
                            }
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
                }, 250);
            });

            $(searchInput).off('focus.learningCertificateSearch').on('focus.learningCertificateSearch', function () {
                if ($.trim(this.value).length >= 1 && ((listEl && listEl.children.length > 0) || (footerEl && footerEl.style.display !== 'none'))) {
                    searchResults.style.display = 'block';
                }
            });

            $(searchInput).off('keydown.learningCertificateSearch').on('keydown.learningCertificateSearch', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchResults.style.display = 'none';
                    applyCertificateFilters();
                }
            });
        }

        initCertificateAutocomplete();

        $(document).off('click.certificateSearchItem').on('click.certificateSearchItem', '.certificate-search-item', function (event) {
            event.preventDefault();
            var title = String($(this).data('title') || '');
            $('#certificate-search-input, #certificateSearch').val(title);
            var searchResults = document.getElementById('certificate-search-results');
            if (searchResults) searchResults.style.display = 'none';
            applyCertificateFilters();
        });

        document.addEventListener('click', function (e) {
            var searchInput = document.getElementById('certificate-search-input') || document.getElementById('certificateSearch');
            var searchResults = document.getElementById('certificate-search-results');
            if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        $('.certificate-quick-item').off('.learningCertificate').on('click.learningCertificate', function (event) {
            event.preventDefault();
            var $item = $(this);
            var $dropdown = $item.closest('[data-certificate-filter]');
            var val = String($item.data('value'));
            setQuickValue($dropdown, val, $.trim($item.text()));

            var filterType = String($dropdown.data('certificate-filter'));
            if (filterType === 'type') {
                $('input[name="certificate_filter_type"]').prop('checked', false).filter('[value="' + val + '"]').prop('checked', true);
                $('#certificateAdvancedType').val(val);
            } else if (filterType === 'year') {
                $('input[name="certificate_filter_year"]').prop('checked', false).filter('[value="' + val + '"]').prop('checked', true);
            } else if (filterType === 'month') {
                $('input[name="certificate_filter_month"]').prop('checked', false).filter('[value="' + val + '"]').prop('checked', true);
            }

            applyCertificateFilters();
        });

        $(document).off('change.learningCertificateRadio')
            .on('change.learningCertificateRadio', 'input[name="certificate_filter_type"], input[name="certificate_filter_year"], input[name="certificate_filter_month"]', function () {
                var name = this.name;
                var val = String($(this).val());
                if (name === 'certificate_filter_type') {
                    var label = $(this).next('.form-check-label').text() || 'All';
                    setQuickValue($('#certificateFilterBar [data-certificate-filter="type"]'), val, $.trim(label));
                } else if (name === 'certificate_filter_year') {
                    setQuickValue($('#certificateFilterBar [data-certificate-filter="year"]'), val, val === 'all' ? 'All' : val);
                } else if (name === 'certificate_filter_month') {
                    var label = $(this).next('.form-check-label').text() || 'All';
                    setQuickValue($('#certificateFilterBar [data-certificate-filter="month"]'), val, $.trim(label));
                }
                updateAdvancedCount();
            });

        $('.certificate-advanced-input, #certificateSort, #certificate_issuer, #certificate_date_from, #certificate_date_to')
            .off('.learningCertificate')
            .on('input.learningCertificate change.learningCertificate', updateAdvancedCount);
        $('#certificateSort').off('.learningCertificate').on('change.learningCertificate', updateAdvancedCount);
        $('#certificateApplyFilter, #certificateModalApplyFilter, .certificate-apply-filter').off('.learningCertificate').on('click.learningCertificate', applyCertificateFilters);

        $('#certificateMoreFiltersModal').off('show.bs.modal.learningCertificate').on('show.bs.modal.learningCertificate', function () {
            restoreCertificateInputs();
            updateAdvancedCount();
        });

        $('#certificateModalReset, #certificateResetFilter').off('.learningCertificate').on('click.learningCertificate', function () {
            clearTimeout(window.learningCertificateSearchTimer);
            $('#certificate-search-input, #certificateSearch').val('');
            var searchResults = document.getElementById('certificate-search-results');
            if (searchResults) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
            }
            replaceCertificateState({});
            restoreCertificateInputs();
            updateAdvancedCount();
            reloadCertificate(cleanCertificateParams(certificateParams()));
        });

        $('#certificateDownloadExcel').off('.learningCertificate').on('click.learningCertificate', function () {
            var params = collectCertificateFilters();
            params.delete('certificate_page');
            params.set('certificate_export', '1');
            var url = @json(route('user.learning.dashboard.partial', ['tab' => 'certificate']));
            window.location.href = url + '?' + params.toString();
        });

        $('.certificate-sort-heading').off('.learningCertificate').on('click.learningCertificate', function () {
            var params = collectCertificateFilters();
            params.set('certificate_sort', String($(this).data('sort')));
            reloadCertificate(params);
        });

        $('.certificate-pagination a').off('.learningCertificate').on('click.learningCertificate', function (event) {
            event.preventDefault();
            var target = new URL(this.href, window.location.origin);
            reloadCertificate(target.searchParams, false);
        });

        $('.certificate-copy-btn').off('.learningCertificate').on('click.learningCertificate', function () {
            var $button = $(this);
            var value = String($button.data('copy'));
            var copyTitle = $button.attr('title') || 'Copy Credential ID';
            var copied = function () {
                $button.addClass('text-success').attr('title', 'Copied');
                setTimeout(function () { $button.removeClass('text-success').attr('title', copyTitle); }, 1400);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(copied);
                return;
            }

            var input = document.createElement('textarea');
            input.value = value;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            copied();
        });

        function showCertificateToast(message, type) {
            type = type || 'success';
            if (window.Toastify) {
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right'
                }).showToast();
                return;
            }

            var bgClass = type === 'success' ? 'bg-success text-white' : 'bg-danger text-white';
            var icon = type === 'success' ? 'ti ti-check' : 'ti ti-alert-circle';

            var toastHtml = '<div class="toast align-items-center ' + bgClass + ' border-0 show" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="d-flex">' +
                    '<div class="toast-body d-flex align-items-center gap-2">' +
                        '<i class="' + icon + '" aria-hidden="true"></i>' +
                        '<span>' + message + '</span>' +
                    '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" data-dismiss="toast" aria-label="Close"></button>' +
                '</div>' +
            '</div>';

            var $container = $('#owl-toast-wrapper');
            if (!$container.length) {
                $container = $('<div id="owl-toast-wrapper" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>').appendTo('body');
            }

            var $toast = $(toastHtml).appendTo($container);

            setTimeout(function () {
                $toast.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }

        async function shareCertificate(url, title, text) {
            var shareUrl = url || window.location.href;
            var shareTitle = title || 'My OWL Learning Certificate';
            var shareText = text || 'I have completed this learning and earned an official certificate from OWL Learning Hub.';

            var shareData = {
                title: shareTitle,
                text: shareText,
                url: shareUrl
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(shareUrl);
                    } else {
                        var input = document.createElement('textarea');
                        input.value = shareUrl;
                        input.style.position = 'fixed';
                        input.style.opacity = '0';
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                    }
                    showCertificateToast('Certificate link copied to clipboard.', 'success');
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Error sharing certificate:', err);
                }
            }
        }

        $(document).off('click.learningCertificateShare', '.btn-share-certificate')
            .on('click.learningCertificateShare', '.btn-share-certificate', function (event) {
                event.preventDefault();
                var $button = $(this);
                var url = String($button.data('url') || '');
                var title = String($button.data('title') || '');
                var text = String($button.data('text') || '');
                shareCertificate(url, title, text);
            });

        $('[data-bs-toggle="popover"].certificate-playlist-badge, [data-toggle="popover"].certificate-playlist-badge').popover({
            container: 'body',
            boundary: 'viewport',
            html: true,
            sanitize: false
        });

        initCertificateHoverPreview();

        readCertificateStateFromUrl();
        restoreCertificateInputs();
        updateAdvancedCount();
    }
</script>
