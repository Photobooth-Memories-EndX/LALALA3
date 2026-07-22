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

        function certificateParams() {
            return new URLSearchParams(window.location.search);
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
            var query = params.toString();
            history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));
            _activeTab = null;
            loadLearningTab('certificate');
        }

        function setQuickValue($dropdown, value, label) {
            $dropdown.attr('data-selected-value', value);
            $dropdown.find('.certificate-quick-value').val(value);
            $dropdown.find('.certificate-quick-item').removeClass('active')
                .filter('[data-value="' + value + '"]').addClass('active');
            $dropdown.find('.dropdown-toggle')
                .toggleClass('active', value !== 'all')
                .find('.certificate-filter-value').text(label);
        }

        function hydrateQuickFilters() {
            $('#certificateFilterBar [data-certificate-filter]').each(function () {
                var $dropdown = $(this);
                var value = String($dropdown.find('.certificate-quick-value').val()
                    || $dropdown.attr('data-selected-value')
                    || 'all');
                var $activeItem = $dropdown.find('.certificate-quick-item').filter(function () {
                    return String($(this).data('value')) === value;
                }).first();
                var label = $activeItem.length ? $.trim($activeItem.text()) : 'All';

                setQuickValue($dropdown, value, label);
            });

            var type = String($('#certificateFilterBar [data-certificate-filter="type"] .certificate-quick-value').val() || 'all');
            $('#certificateAdvancedType').val(type);
        }

        function updateAdvancedCount() {
            var count = $('.certificate-advanced-input').filter(function () {
                return $.trim($(this).val()) !== '';
            }).length;

            $('#certificateMoreFiltersCount')
                .toggleClass('d-none', count === 0)
                .text(count || '');
        }

        function collectCertificateFilters() {
            var params = cleanCertificateParams(certificateParams());
            var search = $.trim($('#certificateSearch').val());

            $('#certificateFilterBar .certificate-quick-value').each(function () {
                var name = String(this.name);
                var value = String($(this).val() || 'all');
                if (value !== 'all') {
                    params.set(name, value);
                }
            });

            $('.certificate-advanced-input').each(function () {
                var value = $.trim($(this).val());
                if (value) params.set(String(this.name), value);
            });

            var advancedType = String($('#certificateAdvancedType').val() || 'all');
            if (advancedType !== 'all') params.set('certificate_filter_type', advancedType);

            var sort = String($('#certificateSort').val() || 'issued_desc');
            if (sort !== 'issued_desc') params.set('certificate_sort', sort);
            if (search) params.set('certificate_search', search);

            return params;
        }

        function applyCertificateFilters() {
            clearTimeout(window.learningCertificateSearchTimer);
            reloadCertificate(collectCertificateFilters());
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
        $('#certificateApplyFilter, .certificate-apply-filter').off('.learningCertificate').on('click.learningCertificate', applyCertificateFilters);

        $('#certificateModalReset').off('.learningCertificate').on('click.learningCertificate', function () {
            $('.certificate-advanced-input').val('');
            $('#certificateAdvancedType').val('all').trigger('change.learningCertificate');
            $('#certificateSort').val('issued_desc');
            updateAdvancedCount();
        });

        $('#certificateResetFilter').off('.learningCertificate').on('click.learningCertificate', function () {
            clearTimeout(window.learningCertificateSearchTimer);
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

        hydrateQuickFilters();
        updateAdvancedCount();
    }
</script>
