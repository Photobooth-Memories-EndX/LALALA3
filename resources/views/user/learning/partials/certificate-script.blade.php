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
            var canHover = window.matchMedia
                && window.matchMedia('(min-width: 768px) and (hover: hover)').matches;
            var previewId = 'certificateHoverPreview';
            var $triggers = $('.certificate-preview-trigger[data-certificate-preview-url]');

            if (!canHover || !$triggers.length) {
                $('#' + previewId).remove();
                return;
            }

            var previewCache = window._certificatePreviewHtmlCache || {};
            window._certificatePreviewHtmlCache = previewCache;

            var $preview = $('#' + previewId);
            if (!$preview.length) {
                $preview = $(
                    '<aside class="certificate-hover-preview" id="' + previewId + '" role="dialog" aria-hidden="true" aria-label="Certificate preview">' +
                        '<div class="certificate-hover-preview-header">' +
                            '<div class="certificate-hover-preview-heading"></div>' +
                            '<button type="button" class="certificate-hover-preview-close" aria-label="Close certificate preview">' +
                                '<i class="ti ti-x" aria-hidden="true"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="certificate-hover-preview-document">' +
                            '<iframe class="certificate-hover-preview-frame" title="Certificate document preview" sandbox=""></iframe>' +
                            '<div class="certificate-hover-preview-state" aria-live="polite">' +
                                '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>' +
                                '<span>Loading certificate preview...</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="certificate-hover-preview-footer">' +
                            '<a class="btn btn-primary certificate-hover-preview-link" href="#">' +
                                '<span>View Full Certificate</span>' +
                                '<i class="ti ti-arrow-right" aria-hidden="true"></i>' +
                            '</a>' +
                        '</div>' +
                    '</aside>'
                ).appendTo(document.body);
            }

            var $heading = $preview.find('.certificate-hover-preview-heading');
            var $documentViewport = $preview.find('.certificate-hover-preview-document');
            var $frame = $preview.find('.certificate-hover-preview-frame');
            var $state = $preview.find('.certificate-hover-preview-state');
            var $fullLink = $preview.find('.certificate-hover-preview-link');
            var openTimer = null;
            var closeTimer = null;
            var activeTrigger = null;
            var activeUrl = '';
            var renderedUrl = '';

            function showState(message, isError) {
                $frame.hide();
                $state
                    .removeClass('d-none')
                    .toggleClass('text-danger', Boolean(isError))
                    .html(
                        isError
                            ? '<i class="ti ti-alert-circle fs-2" aria-hidden="true"></i><span>' + message + '</span>'
                            : '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>' + message + '</span>'
                    );
            }

            function sizeFrame() {
                var sourceWidth = 930;
                var displayWidth = $documentViewport.innerWidth();
                var displayHeight = $documentViewport.innerHeight();
                var scale = Math.min(1, displayWidth / sourceWidth);

                $frame.css({
                    width: sourceWidth + 'px',
                    height: Math.ceil(displayHeight / scale) + 'px',
                    transform: 'scale(' + scale + ')'
                });
            }

            function positionPreview($trigger) {
                var rect = $trigger[0].getBoundingClientRect();
                var gap = 12;
                var edge = 12;
                var previewWidth = $preview.outerWidth();
                var previewHeight = $preview.outerHeight();
                var left = rect.right + gap;
                var top = rect.top;

                if (left + previewWidth > window.innerWidth - edge) {
                    left = rect.left - previewWidth - gap;
                }

                left = Math.max(edge, Math.min(left, window.innerWidth - previewWidth - edge));
                top = Math.max(edge, Math.min(top, window.innerHeight - previewHeight - edge));

                $preview.css({ left: Math.round(left) + 'px', top: Math.round(top) + 'px' });
            }

            function renderHtml(html, url) {
                if (activeUrl !== url) return;

                showState('Loading certificate preview...', false);
                $frame.off('load.certificatePreview').one('load.certificatePreview', function () {
                    if (activeUrl !== url) return;
                    sizeFrame();
                    $state.addClass('d-none');
                    $frame.show();
                    renderedUrl = url;
                });
                $frame[0].srcdoc = html;
            }

            function loadPreview(url) {
                if (renderedUrl === url) {
                    sizeFrame();
                    $state.addClass('d-none');
                    $frame.show();
                    return;
                }

                if (typeof previewCache[url] === 'string') {
                    renderHtml(previewCache[url], url);
                    return;
                }

                showState('Loading certificate preview...', false);

                if (previewCache[url] && typeof previewCache[url].done === 'function') {
                    previewCache[url].done(function (html) {
                        if (activeUrl === url) renderHtml(html, url);
                    });
                    return;
                }

                var request = $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'html',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                previewCache[url] = request;

                request.done(function (html) {
                    previewCache[url] = html;
                    if (activeUrl === url) renderHtml(html, url);
                }).fail(function () {
                    delete previewCache[url];
                    if (activeUrl === url) {
                        showState('Certificate preview could not be loaded.', true);
                    }
                });
            }

            function openPreview(trigger) {
                var $trigger = $(trigger);
                var url = String($trigger.data('certificate-preview-url') || '');
                if (!url) return;

                clearTimeout(closeTimer);
                if (activeTrigger && activeTrigger !== trigger) {
                    $(activeTrigger).attr('aria-expanded', 'false');
                }

                activeTrigger = trigger;
                activeUrl = url;
                $trigger.attr({ 'aria-expanded': 'true', 'aria-controls': previewId });
                $heading.text(String($trigger.data('certificate-preview-title') || 'Certificate'));
                $fullLink.attr('href', $trigger.attr('href'));
                $preview.addClass('is-visible').attr('aria-hidden', 'false');
                positionPreview($trigger);
                loadPreview(url);
            }

            function closePreview(immediate) {
                clearTimeout(openTimer);
                clearTimeout(closeTimer);

                var close = function () {
                    if (activeTrigger) $(activeTrigger).attr('aria-expanded', 'false');
                    activeTrigger = null;
                    activeUrl = '';
                    $preview.removeClass('is-visible').attr('aria-hidden', 'true');
                };

                if (immediate) {
                    close();
                } else {
                    closeTimer = setTimeout(close, 180);
                }
            }

            $triggers.off('.certificatePreview')
                .on('mouseenter.certificatePreview', function () {
                    var trigger = this;
                    clearTimeout(openTimer);
                    clearTimeout(closeTimer);
                    openTimer = setTimeout(function () { openPreview(trigger); }, 150);
                })
                .on('mouseleave.certificatePreview', function () { closePreview(false); })
                .on('focusin.certificatePreview', function () { openPreview(this); })
                .on('focusout.certificatePreview', function () { closePreview(false); });

            $preview.off('.certificatePreview')
                .on('mouseenter.certificatePreview', function () { clearTimeout(closeTimer); })
                .on('mouseleave.certificatePreview', function () { closePreview(false); });

            $preview.find('.certificate-hover-preview-close')
                .off('.certificatePreview')
                .on('click.certificatePreview', function () { closePreview(true); });

            $(document).off('keydown.certificatePreview').on('keydown.certificatePreview', function (event) {
                if (event.key === 'Escape') closePreview(true);
            });

            $(window).off('resize.certificatePreview scroll.certificatePreview')
                .on('resize.certificatePreview scroll.certificatePreview', function () { closePreview(true); });
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
