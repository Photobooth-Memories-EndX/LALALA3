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

        function readCertificateStateFromUrl() {
            var params = certificateParams();
            var nextState = {};

            Object.keys(certificateDefaults).forEach(function (key) {
                nextState[key] = params.has(key)
                    ? String(params.get(key) || certificateDefaults[key])
                    : certificateDefaults[key];
            });

            return replaceCertificateState(nextState);
        }

        function cleanCertificateParams(params) {
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('certificate_') === 0) {
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
            $('#certificateSearch').val(certificateFilterState.certificate_search || '');

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
            nextState.certificate_search = $.trim($('#certificateSearch').val());

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

        $('#certificateSearch').off('.learningCertificate').on('input.learningCertificate', function () {
            var value = $.trim(this.value);
            clearTimeout(window.learningCertificateSearchTimer);
            window.learningCertificateSearchTimer = setTimeout(function () {
                var params = certificateParams();
                params.delete('certificate_search');
                params.delete('certificate_page');
                if (value) params.set('certificate_search', value);
                reloadCertificate(params);
            }, 450);
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
        $('#certificateApplyFilter, .certificate-apply-filter').off('.learningCertificate').on('click.learningCertificate', applyCertificateFilters);

        $('#certificateMoreFiltersModal').off('show.bs.modal.learningCertificate').on('show.bs.modal.learningCertificate', function () {
            restoreCertificateInputs();
            updateAdvancedCount();
        });

        $('#certificateModalReset, #certificateResetFilter').off('.learningCertificate').on('click.learningCertificate', function () {
            clearTimeout(window.learningCertificateSearchTimer);
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

        $('[data-toggle="popover"].certificate-playlist-badge').popover({
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
