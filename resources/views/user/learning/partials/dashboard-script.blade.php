@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    {{-- ApexCharts: dimuat di awal agar tersedia saat tab Monitoring (AJAX) dirender. --}}
    <script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    {{-- Learning Record: search, filter, sorting & pagination client-side (jQuery, tanpa AJAX). --}}
    <script src="{{ asset('js/learning-record.js') }}"></script>

    <script>
        var _activeTab = null;
        var _learningTabs = ['ongoing', 'record', 'assessment', 'certificate'];
        var _ongoingMode = null;
        var _recordMode = null;
        var _tabFetchSequence = 0;
        var _activeFetchController = null;

        function learningTabFromUrl() {
            var tab = String(new URLSearchParams(window.location.search).get('learning_tab') || 'ongoing');
            return _learningTabs.indexOf(tab) !== -1 ? tab : 'ongoing';
        }

        function recordModeFromUrl() {
            var params = new URLSearchParams(window.location.search);
            var mode = String(params.get('record_mode') || 'online').toLowerCase();
            return ['online', 'offline', 'all', 'export'].indexOf(mode) !== -1 ? mode : 'online';
        }

        function skeletonHtml() {
            return '<div class="card border-0 shadow-sm rounded-4 p-4 mb-3">' +
                '<div class="placeholder-glow">' +
                    '<span class="placeholder col-6 mb-3 rounded-3" style="height:20px"></span>' +
                    '<span class="placeholder col-12 mb-2 rounded-3" style="height:14px"></span>' +
                    '<span class="placeholder col-10 mb-4 rounded-3" style="height:14px"></span>' +
                    '<div class="row g-3">' +
                        '<div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>' +
                        '<div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>' +
                        '<div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function recordSkeletonHtml(mode) {
            mode = mode || 'online';
            var isExport = (mode === 'export');

            var switchInsideHeaderHtml = '<div class="learning-filter-heading fast-filter-header' + (isExport ? ' mb-0' : '') + '">' +
                '<div class="fast-filter-header-copy">' +
                    '<h3 class="card-title fast-filter-title">Quick Filter</h3>' +
                '</div>' +
                '<div class="learning-mode-switch record-mode-switch" role="group" aria-label="Learning Record mode">' +
                    '<button type="button" class="btn learning-mode-btn record-mode-btn ' + (mode === 'online' ? 'btn-primary active' : 'btn-outline-primary') + '" data-mode="online"><i class="ti ti-device-laptop"></i><span>Online</span></button>' +
                    '<button type="button" class="btn learning-mode-btn record-mode-btn ' + (mode === 'offline' ? 'btn-primary active' : 'btn-outline-primary') + '" data-mode="offline"><i class="ti ti-certificate"></i><span>Offline</span></button>' +
                    '<button type="button" class="btn learning-mode-btn record-mode-btn ' + (mode === 'all' ? 'btn-primary active' : 'btn-outline-primary') + '" data-mode="all"><i class="ti ti-stack-2"></i><span>All</span></button>' +
                    '<button type="button" class="btn learning-mode-btn record-mode-btn ' + (mode === 'export' ? 'btn-primary active' : 'btn-outline-primary') + '" data-mode="export"><i class="ti ti-download"></i><span>Export</span></button>' +
                '</div>' +
            '</div>';

            if (isExport) {
                return '<div class="learning-record-shell">' +
                    '<div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">' +
                        '<div class="card-body p-4">' +
                            switchInsideHeaderHtml +
                        '</div>' +
                    '</div>' +
                    '<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 placeholder-glow">' +
                        '<div class="d-flex align-items-center gap-3">' +
                            '<span class="placeholder rounded-3" style="width:52px;height:52px;"></span>' +
                            '<div class="flex-grow-1">' +
                                '<span class="placeholder col-4 mb-2 d-block rounded-2" style="height:20px;"></span>' +
                                '<span class="placeholder col-8 d-block rounded-2" style="height:14px;"></span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="row g-3 placeholder-glow">' +
                        '<div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-4"><span class="placeholder col-3 mb-3 rounded-pill" style="height:20px;"></span><span class="placeholder col-8 mb-2 rounded-2" style="height:20px;"></span><span class="placeholder col-12 mb-4 rounded-2" style="height:36px;"></span><span class="placeholder col-12 rounded-3" style="height:40px;"></span></div></div>' +
                        '<div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-4"><span class="placeholder col-3 mb-3 rounded-pill" style="height:20px;"></span><span class="placeholder col-8 mb-2 rounded-2" style="height:20px;"></span><span class="placeholder col-12 mb-4 rounded-2" style="height:36px;"></span><span class="placeholder col-12 rounded-3" style="height:40px;"></span></div></div>' +
                        '<div class="col-12 col-md-4"><div class="card border-0 shadow-sm rounded-4 p-4"><span class="placeholder col-3 mb-3 rounded-pill" style="height:20px;"></span><span class="placeholder col-8 mb-2 rounded-2" style="height:20px;"></span><span class="placeholder col-12 mb-4 rounded-2" style="height:36px;"></span><span class="placeholder col-12 rounded-3" style="height:40px;"></span></div></div>' +
                    '</div>' +
                '</div>';
            }

            var tableRows = '';
            for (var i = 0; i < 5; i++) {
                tableRows += '<tr>' +
                    '<td class="text-center"><span class="placeholder col-6" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-10 mb-1 d-block" style="height:14px;"></span><span class="placeholder col-5 d-block" style="height:10px;"></span></td>' +
                    '<td><span class="placeholder col-7" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-7" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-8" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-6" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-6" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-8" style="height:14px;"></span></td>' +
                    '<td><span class="placeholder col-8" style="height:14px;"></span></td>' +
                    '<td class="text-center"><span class="placeholder col-5" style="height:14px;"></span></td>' +
                    '<td class="text-center"><span class="placeholder col-5" style="height:14px;"></span></td>' +
                '</tr>';
            }

            return '<div class="learning-record-shell">' +
                '<div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">' +
                    '<div class="card-body p-4 border-bottom placeholder-glow">' +
                        switchInsideHeaderHtml +
                        '<div class="d-flex align-items-center flex-wrap gap-2 mt-3">' +
                            '<span class="placeholder rounded-pill" style="width:110px;height:38px;"></span>' +
                            '<span class="placeholder rounded-pill" style="width:140px;height:38px;"></span>' +
                            '<span class="placeholder rounded-pill" style="width:90px;height:38px;"></span>' +
                            '<span class="placeholder flex-grow-1 rounded-3" style="min-width:200px;height:38px;"></span>' +
                            '<span class="placeholder rounded-3" style="width:38px;height:38px;"></span>' +
                            '<span class="placeholder rounded-3" style="width:38px;height:38px;"></span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card-body p-0">' +
                        '<div class="table-responsive learning-record-scroll">' +
                            '<table class="table table-vcenter card-table mb-0 learning-record-table placeholder-glow">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th class="text-center" style="width:4%">No</th>' +
                                        '<th style="width:24%">Course / Training Title</th>' +
                                        '<th style="width:8%">Type</th>' +
                                        '<th style="width:8%">Category</th>' +
                                        '<th style="width:10%">Classification</th>' +
                                        '<th style="width:8%">Duration</th>' +
                                        '<th style="width:8%">Learning Hour</th>' +
                                        '<th style="width:7%">Progress</th>' +
                                        '<th style="width:10%">Date</th>' +
                                        '<th class="text-center" style="width:8%">Score</th>' +
                                        '<th class="text-center" style="width:6%">Certificate</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' + tableRows + '</tbody>' +
                            '</table>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function showLearningHero(tab) {
            $('[data-learning-hero]')
                .addClass('d-none')
                .attr('aria-hidden', 'true');

            $('[data-learning-hero="' + tab + '"]')
                .removeClass('d-none')
                .attr('aria-hidden', 'false');
        }

        function syncLearningOngoingHero() {
            var template = document.querySelector(
                '#learning-tab-content template[data-learning-ongoing-hero]'
            );
            var heroRegion = document.querySelector('[data-learning-hero="ongoing"]');

            if (!template || !heroRegion) return;

            heroRegion.replaceChildren(template.content.cloneNode(true));
            template.remove();
        }

        function syncLearningAssessmentHero() {
            var template = document.querySelector(
                '#learning-tab-content template[data-learning-assessment-hero]'
            );
            var heroRegion = document.querySelector('[data-learning-hero="assessment"]');

            if (!template || !heroRegion) return;

            heroRegion.replaceChildren(template.content.cloneNode(true));
            template.remove();
        }

        function syncLearningRecordHero() {
            var template = document.querySelector(
                '#learning-tab-content template[data-learning-record-hero]'
            );
            var heroRegion = document.querySelector('[data-learning-hero="record"]');

            if (!template || !heroRegion) return;

            heroRegion.replaceChildren(template.content.cloneNode(true));
            template.remove();
        }

        function syncLearningCertificateHero() {
            var template = document.querySelector(
                '#learning-tab-content template[data-learning-certificate-hero]'
            );
            var heroRegion = document.querySelector('[data-learning-hero="certificate"]');

            if (!template || !heroRegion) return;

            heroRegion.replaceChildren(template.content.cloneNode(true));
            template.remove();
        }

        function loadLearningTab(tab) {
            if (_activeTab === tab) return;

            var $openFilterModal = $('#assessmentMoreFiltersModal.show, #certificateMoreFiltersModal.show');
            if ($openFilterModal.length) {
                $openFilterModal
                    .one('hidden.bs.modal.learningAssessment', function () {
                        loadLearningTab(tab);
                    })
                    .modal('hide');
                return;
            }

            _activeTab = tab;

            // Update active button state
            $('.learning-tab-btn')
                .removeClass('btn-primary')
                .addClass('btn-white')
                .attr('aria-selected', 'false');
            $('.learning-tab-btn[data-target="#tab-' + tab + '"]')
                .removeClass('btn-white')
                .addClass('btn-primary')
                .attr('aria-selected', 'true');
            showLearningHero(tab);

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#assessmentTable')) {
                $('#assessmentTable').DataTable().destroy();
            }
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#certificateTable')) {
                $('#certificateTable').DataTable().destroy();
            }

            // Show skeleton while loading
            if (tab === 'record') {
                $('#learning-tab-content').html(recordSkeletonHtml(_recordMode || recordModeFromUrl()));
            } else {
                $('#learning-tab-content').html(skeletonHtml());
            }

            // Forward current URL query params so filters and pagination work
            var params = new URLSearchParams(window.location.search);
            Array.from(params.keys()).forEach(function (key) {
                if (tab !== 'ongoing' && key.indexOf('ongoing_') === 0) params.delete(key);
                if (tab !== 'record' && key.indexOf('record_') === 0) params.delete(key);
                if (tab !== 'assessment' && key.indexOf('assessment_') === 0) params.delete(key);
                if (tab !== 'certificate' && key.indexOf('certificate_') === 0) params.delete(key);
            });
            if (tab === 'ongoing' && _ongoingMode) {
                params.set('ongoing_mode', _ongoingMode);
            }
            if (tab === 'record') {
                var rm = _recordMode || recordModeFromUrl();
                if (rm) params.set('record_mode', rm);
            }
            var fetchUrl = '{{ route("user.learning.dashboard.partial", ["tab" => "__TAB__"]) }}'.replace('__TAB__', tab);
            if (params.toString()) {
                fetchUrl += '?' + params.toString();
            }

            if (_activeFetchController) {
                try { _activeFetchController.abort(); } catch (e) {}
            }
            _activeFetchController = (typeof AbortController !== 'undefined') ? new AbortController() : null;
            var thisFetchSeq = ++_tabFetchSequence;

            fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: _activeFetchController ? _activeFetchController.signal : undefined
            })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                if (thisFetchSeq !== _tabFetchSequence) return;
                $('#learning-tab-content').html(html);
                syncLearningOngoingHero();
                syncLearningAssessmentHero();
                syncLearningRecordHero();
                syncLearningCertificateHero();
                initLearningAssessment();
                initLearningCertificate();
                initLearningRecord();
                initOngoingSearchAutocomplete();
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                if (thisFetchSeq !== _tabFetchSequence) return;
                _activeTab = null;
                $('#learning-tab-content').html(
                    '<div class="alert alert-danger m-4">Gagal memuat konten. Silakan refresh halaman.</div>'
                );
            });
        }

        // ── Live Autocomplete Search for Ongoing Learning ───────────────────────────
        function initOngoingSearchAutocomplete() {
            var searchInput = document.getElementById('ongoing-search-input');
            var searchResults = document.getElementById('ongoing-search-results');
            var debounceTimer = null;

            if (!searchInput || !searchResults) return;

            var listEl = searchResults.querySelector('.autocomplete-list-group') || searchResults;
            var footerEl = searchResults.querySelector('.search-footer');

            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function getOngoingMode() {
                if (_ongoingMode === 'playlist') return 'playlist';
                if (_ongoingMode === 'mooc') return 'mooc';
                var modeInput = document.querySelector('input[name="ongoing_mode"]');
                if (modeInput && modeInput.value === 'playlist') return 'playlist';
                if (modeInput && modeInput.value === 'mooc') return 'mooc';
                var urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('ongoing_mode') || 'content';
            }

            searchInput.addEventListener('input', function () {
                var query = this.value.trim();

                clearTimeout(debounceTimer);

                if (query.length < 1) {
                    searchResults.style.display = 'none';
                    if (listEl) listEl.innerHTML = '';
                    if (footerEl) footerEl.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(function () {
                    var mode = getOngoingMode();
                    fetch('/user/learning/ongoing/search-autocomplete?q=' + encodeURIComponent(query) + '&mode=' + encodeURIComponent(mode), {
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
                                var badgeType = item.type === 'playlist' ? 'bg-teal-lt text-teal' : 'bg-primary-lt text-primary';
                                var typeLabel = item.type ? escapeHtml(item.type.toUpperCase()) : 'CONTENT';

                                html += '<a href="' + escapeHtml(item.url) + '" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle ongoing-search-item" data-title="' + escapeHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeHtml(item.title) + '</span>' +
                                        (item.progress !== undefined ? '<span class="badge ' + badgeType + ' small flex-shrink-0">' + item.progress + '%</span>' : '') +
                                    '</div>' +
                                    '<div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">' +
                                        '<span class="text-truncate">' + escapeHtml(item.category || '') + '</span>' +
                                        '<span class="badge bg-secondary-lt text-secondary" style="font-size: 0.65rem;">' + typeLabel + '</span>' +
                                    '</div>' +
                                '</a>';
                            });
                        }
                        if (listEl) listEl.innerHTML = html;

                        var keyword = searchInput.value.trim();
                        if (footerEl) {
                            if (data && data.length > 0) {
                                footerEl.innerHTML = `
                                    <a href="/my-learning?learning_tab=ongoing&q=${encodeURIComponent(keyword)}" class="dropdown-item fw-semibold text-primary py-2 d-flex align-items-center justify-content-center gap-2">
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

            // Show dropdown again on focus if query exists
            searchInput.addEventListener('focus', function () {
                if (this.value.trim().length >= 1 && ((listEl && listEl.children.length > 0) || (footerEl && footerEl.style.display !== 'none'))) {
                    searchResults.style.display = 'block';
                }
            });
        }

        // Hide dropdown menu when clicking outside search container
        document.addEventListener('click', function (e) {
            var searchInput = document.getElementById('ongoing-search-input');
            var searchResults = document.getElementById('ongoing-search-results');
            if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // ── Quick dropdown filters Learning On Going (Type / Status / Year / Month) ──
        // Terapkan pilihan via AJAX (tanpa reload halaman): perbarui query string di URL
        // lalu muat ulang partial tab ongoing memakai endpoint yang sudah ada.
        function applyOngoingQuickFilter(param, value, isArray) {
            var params = new URLSearchParams(window.location.search);

            // Hapus nilai lama param tsb (bentuk "param", "param[]" maupun "param[0]")
            // dan reset pagination agar hasil filter mulai dari halaman pertama.
            Array.from(params.keys()).forEach(function (key) {
                if (key === param || key.indexOf(param + '[') === 0 || key === 'ongoing_page') {
                    params.delete(key);
                }
            });

            if (value !== '' && value !== 'all') {
                params.set(isArray ? param + '[]' : param, value);
            }

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

            _activeTab = null; // paksa loadLearningTab memuat ulang partial ongoing
            loadLearningTab('ongoing');
        }

        // Delegated: item dropdown ikut ter-render ulang setiap partial dimuat via AJAX.
        // Klik item yang sudah aktif = batalkan pilihan (toggle, tanpa opsi "All" pada Type).
        $(document).on('click', '.ongoing-quick-item', function (e) {
            e.preventDefault();
            applyOngoingQuickFilter(
                String($(this).data('param')),
                $(this).hasClass('active') ? '' : String($(this).data('value')),
                $(this).data('array') == 1
            );
        });

        // ── Mode switch Learning On Going: Content | Playlist ────────────────────────
        // Dua flow independen — pindah mode me-reset seluruh filter ongoing, lalu
        // partial mode terpilih dimuat via AJAX (tanpa reload halaman).
        $(document).on('click', '.ongoing-mode-btn', function () {
            var mode   = String($(this).data('mode'));
            var params = new URLSearchParams(window.location.search);

            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('ongoing_') === 0) {
                    params.delete(key);
                }
            });

            _ongoingMode = (mode === 'playlist' || mode === 'mooc') ? mode : null;

            if (_ongoingMode) {
                params.set('ongoing_mode', _ongoingMode);
            }

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

            _activeTab = null; // paksa loadLearningTab memuat ulang partial ongoing
            loadLearningTab('ongoing');
        });

        // ── Mode switch Learning Record: Online | Offline | All | Export ─────────────
        $(document).on('click', '.record-mode-btn', function (e) {
            e.preventDefault();
            var mode = String($(this).data('mode') || 'online').toLowerCase();
            if (['online', 'offline', 'all', 'export'].indexOf(mode) === -1) {
                mode = 'online';
            }

            var params = new URLSearchParams(window.location.search);
            params.set('learning_tab', 'record');

            // Bersihkan filter lama jika berpindah mode agar tidak konflik
            if (mode !== _recordMode) {
                Array.from(params.keys()).forEach(function (key) {
                    if (key.indexOf('record_filter_') === 0 || key === 'record_page' || key === 'page' || key === 'record_search') {
                        params.delete(key);
                    }
                });
            }

            _recordMode = mode;
            params.set('record_mode', mode);

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

            _activeTab = null; // paksa loadLearningTab memuat ulang partial record
            loadLearningTab('record');
        });

        // ── Filter dropdowns & search for Record Offline & All ───────────────────────
        $(document).on('click', '.record-offline-filter-item, .record-all-filter-item', function (e) {
            e.preventDefault();
            var param = String($(this).data('param'));
            var value = String($(this).data('value'));
            var params = new URLSearchParams(window.location.search);

            // Reset pagination saat filter berganti
            params.delete('page');
            params.delete('record_page');

            if (value === 'all' || value === '') {
                params.delete(param);
            } else {
                params.set(param, value);
            }

            params.set('learning_tab', 'record');
            if (_recordMode) params.set('record_mode', _recordMode);

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('click', '#recordOfflineApplyBtn', function () {
            var q = $('#recordOfflineSearch').val() || '';
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('record_page');
            if (q.trim()) {
                params.set('record_search', q.trim());
            } else {
                params.delete('record_search');
            }
            params.set('learning_tab', 'record');
            params.set('record_mode', 'offline');
            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('click', '#recordOfflineResetBtn', function () {
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('record_') === 0 && key !== 'record_mode') {
                    params.delete(key);
                }
            });
            params.set('learning_tab', 'record');
            params.set('record_mode', 'offline');
            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        // ── More Filter Offline handlers ──────────────────────────────────────
        $(document).on('click', '#btnApplyMoreFilterModal', function () {
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('record_page');

            var filterKeys = [
                'record_filter_category',
                'record_filter_sub_classification',
                'record_filter_batch',
                'record_filter_delivery_method',
                'record_filter_provider',
                'record_filter_attendance',
                'record_filter_certificate'
            ];

            filterKeys.forEach(function (k) {
                var val = $('[name="' + k + '"]').val();
                if (val && val !== 'all') {
                    params.set(k, val);
                } else {
                    params.delete(k);
                }
            });

            params.set('learning_tab', 'record');
            params.set('record_mode', 'offline');

            var modalEl = document.getElementById('recordOfflineMoreFilterModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                } else {
                    $(modalEl).modal('hide');
                }
            }

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('click', '#btnResetMoreFilterModal', function () {
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('record_page');

            var filterKeys = [
                'record_filter_category',
                'record_filter_sub_classification',
                'record_filter_batch',
                'record_filter_delivery_method',
                'record_filter_provider',
                'record_filter_attendance',
                'record_filter_certificate'
            ];

            filterKeys.forEach(function (k) {
                params.delete(k);
                $('[name="' + k + '"]').val('all');
            });

            params.set('learning_tab', 'record');
            params.set('record_mode', 'offline');

            var modalEl = document.getElementById('recordOfflineMoreFilterModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                } else {
                    $(modalEl).modal('hide');
                }
            }

            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        // Clear single more filter badge
        $(document).on('click', '.record-offline-clear-single-filter', function (e) {
            e.preventDefault();
            var param = $(this).data('param');
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('record_page');
            params.delete(param);
            params.set('learning_tab', 'record');
            params.set('record_mode', 'offline');
            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('click', '#recordOfflineClearAllMoreFilters', function (e) {
            e.preventDefault();
            $('#btnResetMoreFilterModal').trigger('click');
        });

        // ── View Detail Offline Modal Population ──────────────────────────────
        $(document).on('click', '.btn-record-offline-detail', function (e) {
            e.preventDefault();
            var recordId = $(this).data('record-id');
            var dataEl = document.getElementById('record-offline-data-' + recordId);
            if (!dataEl) return;

            var d = {};
            try {
                d = JSON.parse(dataEl.textContent);
            } catch (err) {
                console.error('Failed to parse record offline data:', err);
                return;
            }

            function fallback(val, def) {
                if (val === undefined || val === null || val === '') return def !== undefined ? def : '-';
                return val;
            }

            // Training Information
            $('#dtCourseName').text(fallback(d.course_name, d.title));
            $('#detailCourseSubHeader').text(fallback(d.subtitle, d.classification));
            $('#dtSubCourseName').text(fallback(d.sub_course_name));
            $('#dtType').text(fallback(d.type));
            $('#dtCategory').text(fallback(d.category));
            $('#dtLevel').text(fallback(d.level));
            $('#dtBatch').text(fallback(d.batch));
            $('#dtClassification').text(fallback(d.classification));
            $('#dtSubClassification').text(fallback(d.sub_classification));
            $('#dtDeliveryMethod').text(fallback(d.delivery_method));
            $('#dtSubDeliveryMethod').text(fallback(d.sub_delivery_method));
            $('#dtProvider').text(fallback(d.provider));
            $('#dtProviderName').text(fallback(d.provider_name));

            // Schedule & Venue
            $('#dtDateDisplay').text(fallback(d.date_display));
            $('#dtTimeDisplay').text(fallback(d.time_display));
            $('#dtDuration').text(fallback(d.duration));
            $('#dtLearningHours').text(fallback(d.learning_hour));
            $('#dtVenue').text(fallback(d.venue));
            $('#dtInstructor').text(fallback(d.instructor));

            // My Learning Result
            var progress = parseInt(d.progress, 10) || 0;
            if (progress >= 100) {
                $('#dtProgressBadge').html('<span class="badge bg-success-lt text-success fw-bold px-2 py-1">100% Completed</span>');
            } else if (progress > 0) {
                $('#dtProgressBadge').html('<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">' + progress + '% In Progress</span>');
            } else {
                $('#dtProgressBadge').html('<span class="badge bg-secondary-lt fw-bold px-2 py-1">0% Not Started</span>');
            }

            var attStatus = fallback(d.attendance_status);
            if (attStatus === 'Present') {
                $('#dtAttendanceBadge').html('<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Present</span>');
            } else if (attStatus === 'Absent') {
                $('#dtAttendanceBadge').html('<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">Absent</span>');
            } else {
                $('#dtAttendanceBadge').html('<span class="badge bg-secondary-lt fw-bold px-2 py-1">-</span>');
            }

            // Attendance Reason: show container if reason exists or status is Absent
            var reason = fallback(d.attendance_reason, '-');
            if (reason !== '-' && reason !== '') {
                $('#dtAttendanceReason').text(reason);
                $('#dtAttendanceReasonContainer').show();
            } else if (attStatus === 'Absent') {
                $('#dtAttendanceReason').text('No reason provided');
                $('#dtAttendanceReasonContainer').show();
            } else {
                $('#dtAttendanceReasonContainer').hide();
            }

            if (d.score !== null && d.score !== undefined) {
                var scoreNum = parseFloat(d.score);
                var scoreClass = scoreNum >= 70 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger';
                $('#dtAssessmentScore').html('<span class="badge ' + scoreClass + ' fw-bold px-2 py-1 fs-5">' + scoreNum + '</span>');
            } else {
                $('#dtAssessmentScore').html('<span class="text-muted fw-normal">-</span>');
            }

            var cert = String(d.certificate || '').toLowerCase();
            if (cert === 'yes') {
                $('#dtCertificateStatus').html('<span class="badge bg-success-lt text-success fw-bold px-2 py-1"><i class="ti ti-certificate me-1"></i>Yes (Issued)</span>');
            } else if (cert === 'no') {
                $('#dtCertificateStatus').html('<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>');
            } else {
                $('#dtCertificateStatus').html('<span class="text-muted fw-normal">-</span>');
            }

            // Evaluation (Kirkpatrick)
            $('#dtL1Program').text(fallback(d.l1_program));
            $('#dtL2Numerical').text(fallback(d.l2_numerical_display));
            $('#dtL2Verbal').text(fallback(d.l2_verbal));
            $('#dtL3Numerical').text(fallback(d.l3_numerical_display));
            $('#dtL3Verbal').text(fallback(d.l3_verbal));
            $('#dtPic').text(fallback(d.pic));

            // Employee Snapshot
            $('#dtEmployeeId').text(fallback(d.employee_id));
            $('#dtParticipantName').text(fallback(d.participant_name));
            $('#dtGrade').text(fallback(d.grade));
            $('#dtJobPosition').text(fallback(d.job_position));
            $('#dtTeam').text(fallback(d.team));
            $('#dtDepartment').text(fallback(d.department));
            $('#dtCompany').text(fallback(d.company));

            // Service Bond
            var bondStatus = fallback(d.bond_status);
            $('#dtBondStatus').text(bondStatus);
            $('#dtBondEndDate').text(fallback(d.bond_end_date_display));
            $('#dtBondAmount').text(fallback(d.bond_amount_formatted));

            if (String(bondStatus).toLowerCase() === 'yes') {
                $('#dtBondNotice').show();
            } else {
                $('#dtBondNotice').hide();
            }

            var modalEl = document.getElementById('recordOfflineDetailModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });

        // ── Attendance Detail Modal Population ──────────────────────────────
        $(document).on('click', '.attendance-detail-trigger', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var recordId = $btn.data('record-id');

            // Attempt to read from data attributes first
            var trainingTitle = ($btn.data('training-title') || '').toString().trim();
            var attendanceStatus = ($btn.data('attendance') || '').toString().trim();
            var rawReason = ($btn.data('attendance-reason') || '').toString().trim();
            var precomputedReason = ($btn.data('attendance-reason-display') || '').toString().trim();
            var trainingDate = ($btn.data('training-date') || '').toString().trim();
            var venue = ($btn.data('venue') || '').toString().trim();

            // If any data is missing and JSON element exists, read from JSON element
            if (recordId) {
                var dataEl = document.getElementById('record-offline-data-' + recordId);
                if (dataEl) {
                    try {
                        var d = JSON.parse(dataEl.textContent);
                        if (!trainingTitle || trainingTitle === '-') trainingTitle = (d.course_name || d.title || '-').toString().trim();
                        if (!attendanceStatus || attendanceStatus === '-') attendanceStatus = (d.attendance_status || '-').toString().trim();
                        if (!rawReason) {
                            rawReason = (d.raw_attendance_reason || (d.attendance_reason !== '-' ? d.attendance_reason : '') || '').toString().trim();
                        }
                        if (!precomputedReason) {
                            precomputedReason = (d.attendance_reason_display || '').toString().trim();
                        }
                        if (!trainingDate || trainingDate === '-') trainingDate = (d.date_display || '-').toString().trim();
                        if (!venue || venue === '-') venue = (d.venue || '-').toString().trim();
                    } catch (err) {
                        console.error('Failed to parse record offline data for attendance:', err);
                    }
                }
            }

            // Fallbacks for display
            if (!trainingTitle || trainingTitle === '-') trainingTitle = '-';
            if (!trainingDate || trainingDate === '-') trainingDate = '-';
            if (!venue || venue === '-') venue = '-';
            if (!attendanceStatus) attendanceStatus = '-';

            // Status badge styling mapping
            var statusLower = attendanceStatus.toLowerCase();
            var badgeClass = 'bg-secondary-lt text-secondary';
            if (statusLower === 'present') {
                badgeClass = 'bg-success-lt text-success';
            } else if (statusLower === 'absent') {
                badgeClass = 'bg-danger-lt text-danger';
            } else if (statusLower === 'excused' || statusLower === 'sick') {
                badgeClass = 'bg-warning-lt text-warning';
            } else if (statusLower === 'leave') {
                badgeClass = 'bg-info-lt text-info';
            }

            // Attendance Reason Logic:
            // IF attendance_reason tidak null dan tidak kosong -> tampilkan nilai attendance_reason
            // IF attendance_reason NULL / kosong:
            //   - untuk Present: "Employee attended the training."
            //   - untuk non-Present: "No attendance reason recorded."
            // Jangan pernah menampilkan null, undefined, -, atau N/A.
            var finalReason = '';
            var isRawEmpty = !rawReason ||
                             rawReason === '-' ||
                             rawReason.toLowerCase() === 'null' ||
                             rawReason.toLowerCase() === 'undefined' ||
                             rawReason.toLowerCase() === 'n/a';

            if (!isRawEmpty) {
                finalReason = rawReason;
            } else if (precomputedReason &&
                       precomputedReason !== '-' &&
                       precomputedReason.toLowerCase() !== 'null' &&
                       precomputedReason.toLowerCase() !== 'undefined' &&
                       precomputedReason.toLowerCase() !== 'n/a') {
                finalReason = precomputedReason;
            } else if (statusLower === 'present') {
                finalReason = 'Employee attended the training.';
            } else {
                finalReason = 'No attendance reason recorded.';
            }

            // Safe DOM population using jQuery .text() to prevent XSS
            $('#attendanceTrainingTitle').text(trainingTitle);
            $('#attendanceStatus').removeClass().addClass('badge fw-bold px-2.5 py-1 ' + badgeClass).text(attendanceStatus);
            $('#attendanceReason').text(finalReason);
            $('#attendanceDate').text(trainingDate);
            $('#attendanceVenue').text(venue);

            var modalEl = document.getElementById('attendanceDetailModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });

        $(document).on('click', '#recordAllApplyBtn', function () {
            var q = $('#recordAllSearch').val() || '';
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('record_page');
            if (q.trim()) {
                params.set('record_search', q.trim());
            } else {
                params.delete('record_search');
            }
            params.set('learning_tab', 'record');
            params.set('record_mode', 'all');
            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('click', '#recordAllResetBtn', function () {
            var params = new URLSearchParams(window.location.search);
            params.delete('page');
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('record_') === 0 && key !== 'record_mode') {
                    params.delete(key);
                }
            });
            params.set('learning_tab', 'record');
            params.set('record_mode', 'all');
            var qs = params.toString();
            history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
            _activeTab = null;
            loadLearningTab('record');
        });

        $(document).on('keydown', '#recordOfflineSearch', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#recordOfflineApplyBtn').trigger('click');
            }
        });

        $(document).on('keydown', '#recordAllSearch', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#recordAllApplyBtn').trigger('click');
            }
        });

        // Back/forward browser: restore the tab encoded in the GET query.
        window.addEventListener('popstate', function () {
            var params = new URLSearchParams(window.location.search);
            var om = params.get('ongoing_mode');
            _ongoingMode = (om === 'playlist' || om === 'mooc') ? om : null;
            var rm = params.get('record_mode');
            _recordMode = ['online', 'offline', 'all', 'export'].indexOf(String(rm).toLowerCase()) !== -1
                ? String(rm).toLowerCase()
                : (params.get('learning_tab') === 'record' ? 'online' : null);
            _activeTab = null;
            loadLearningTab(learningTabFromUrl());
        });

        $(function () {
            // Restore the tab that initiated a full-page GET filter request.
            var initialParams = new URLSearchParams(window.location.search);
            var initialOngoingMode = initialParams.get('ongoing_mode');
            if (initialOngoingMode === 'playlist' || initialOngoingMode === 'mooc') {
                _ongoingMode = initialOngoingMode;
            }
            var initialRecordMode = initialParams.get('record_mode');
            if (initialRecordMode && ['online', 'offline', 'all', 'export'].indexOf(initialRecordMode.toLowerCase()) !== -1) {
                _recordMode = initialRecordMode.toLowerCase();
            } else if (initialParams.get('learning_tab') === 'record') {
                _recordMode = 'online';
            }
            loadLearningTab(learningTabFromUrl());

            // Tab button click handler
            $('.learning-tab-btn').on('click', function () {
                var target = $(this).data('target');          // e.g. "#tab-ongoing"
                var tab    = target.replace('#tab-', '');     // → "ongoing"
                if (_activeTab === tab) return;

                var params = new URLSearchParams();
                if (tab !== 'ongoing') {
                    params.set('learning_tab', tab);
                }
                if (tab === 'ongoing' && _ongoingMode) {
                    params.set('ongoing_mode', _ongoingMode);
                }
                if (tab === 'record' && _recordMode) {
                    params.set('record_mode', _recordMode);
                }
                var query = params.toString();
                history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));
                loadLearningTab(tab);
            });
        });
    </script>
    @include('user.learning.partials.assessment-script')
    @include('user.learning.partials.certificate-script')
@endsection
