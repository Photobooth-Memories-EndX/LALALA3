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

        var certificateDefaults = {
            certificate_filter_type: 'all',
            certificate_filter_year: 'all',
            certificate_filter_month: 'all',
            certificate_filter_status: 'all',
            certificate_number: '',
            certificate_credential: '',
            certificate_participant: '',
            certificate_issuer: '',
            certificate_target: '',
            certificate_date_from: '',
            certificate_date_to: '',
            certificate_sort: 'issued_desc',
            certificate_search: ''
        };
        var certificateAdvancedNames = [
            'certificate_number',
            'certificate_credential',
            'certificate_participant',
            'certificate_issuer',
            'certificate_target',
            'certificate_date_from',
            'certificate_date_to'
        ];

        // Keep one stable object across AJAX partial reloads. The URL remains the
        // durable source of truth, while this object preserves committed modal state.
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
            $('#certificateSearch').val(certificateFilterState.certificate_search);

            $('#certificateFilterBar [data-certificate-filter]').each(function () {
                var $dropdown = $(this);
                var name = String($dropdown.find('.certificate-quick-value').attr('name'));
                var value = certificateFilterState[name] || 'all';
                setQuickValue($dropdown, value, quickFilterLabel($dropdown, value));
            });

            certificateAdvancedNames.forEach(function (name) {
                $('[name="' + name + '"]').val(certificateFilterState[name] || '');
            });

            $('#certificateAdvancedType').val(certificateFilterState.certificate_filter_type || 'all');
            $('#certificateSort').val(certificateFilterState.certificate_sort || 'issued_desc');
        }

        function pendingAdvancedCount() {
            var count = certificateAdvancedNames.reduce(function (total, name) {
                return total + ($.trim($('[name="' + name + '"]').val()) !== '' ? 1 : 0);
            }, 0);

            // Type is also a Fast Filter, so it is intentionally not counted.
            // Sorting is modal-only and follows Learning On Going's non-default count.
            if (String($('#certificateSort').val() || 'issued_desc') !== 'issued_desc') {
                count++;
            }

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

            certificateAdvancedNames.forEach(function (name) {
                nextState[name] = $.trim($('[name="' + name + '"]').val());
            });

            nextState.certificate_filter_type = String($('#certificateAdvancedType').val() || nextState.certificate_filter_type || 'all');
            nextState.certificate_sort = String($('#certificateSort').val() || 'issued_desc');
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
            setQuickValue($dropdown, String($item.data('value')), $.trim($item.text()));

            if (String($dropdown.data('certificate-filter')) === 'type') {
                $('#certificateAdvancedType').val(String($item.data('value')));
            }
        });

        $('#certificateAdvancedType').off('.learningCertificate').on('change.learningCertificate', function () {
            var value = String($(this).val());
            var label = $.trim($(this).find('option:selected').text());
            setQuickValue($('#certificateFilterBar [data-certificate-filter="type"]'), value, label);
        });

        $('.certificate-advanced-input').off('.learningCertificate').on('input.learningCertificate change.learningCertificate', updateAdvancedCount);
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
            var copied = function () {
                $button.addClass('text-success').attr('title', 'Copied');
                setTimeout(function () { $button.removeClass('text-success').attr('title', 'Copy Credential ID'); }, 1400);
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

        $('[data-toggle="popover"].certificate-playlist-badge').popover({
            container: 'body',
            boundary: 'viewport',
            html: true,
            sanitize: false
        });

        readCertificateStateFromUrl();
        restoreCertificateInputs();
        updateAdvancedCount();
    }
</script>
