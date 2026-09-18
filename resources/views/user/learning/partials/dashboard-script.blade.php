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

    function learningTabFromUrl() {
        var tab = String(new URLSearchParams(window.location.search).get('learning_tab') || 'ongoing');
        return _learningTabs.indexOf(tab) !== -1 ? tab : 'ongoing';
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
        $('#learning-tab-content').html(skeletonHtml());

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
        var fetchUrl = '{{ route("user.learning.dashboard.partial", ["tab" => "__TAB__"]) }}'.replace('__TAB__', tab);
        if (params.toString()) {
            fetchUrl += '?' + params.toString();
        }

        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(function (html) {
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
        .catch(function () {
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

    // Back/forward browser: restore the tab encoded in the GET query.
    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        var om = params.get('ongoing_mode');
        _ongoingMode = (om === 'playlist' || om === 'mooc') ? om : null;
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
            var query = params.toString();
            history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));
            loadLearningTab(tab);
        });
    });
</script>
@include('user.learning.partials.assessment-script')
@include('user.learning.partials.certificate-script')
@endsection
