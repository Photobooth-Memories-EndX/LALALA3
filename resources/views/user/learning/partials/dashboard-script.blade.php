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
        if (tab === 'ongoing' && _ongoingMode === 'mooc') {
            params.set('ongoing_mode', 'mooc');
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
            initLearningAssessment();
            initLearningCertificate();
            initLearningRecord();
        })
        .catch(function () {
            _activeTab = null;
            $('#learning-tab-content').html(
                '<div class="alert alert-danger m-4">Gagal memuat konten. Silakan refresh halaman.</div>'
            );
        });
    }

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

        _ongoingMode = mode === 'mooc' ? 'mooc' : null;

        if (mode === 'playlist') {
            params.set('ongoing_mode', 'playlist');
        }

        var qs = params.toString();
        history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

        _activeTab = null; // paksa loadLearningTab memuat ulang partial ongoing
        loadLearningTab('ongoing');
    });

    // Back/forward browser: restore the tab encoded in the GET query.
    window.addEventListener('popstate', function () {
        _ongoingMode = null;
        _activeTab = null;
        loadLearningTab(learningTabFromUrl());
    });

    $(function () {
        // Restore the tab that initiated a full-page GET filter request.
        loadLearningTab(learningTabFromUrl());

        // Tab button click handler
        $('.learning-tab-btn').on('click', function () {
            var target = $(this).data('target');          // e.g. "#tab-ongoing"
            var tab    = target.replace('#tab-', '');     // → "ongoing"
            var params = new URLSearchParams(window.location.search);
            if (tab === 'ongoing') {
                params.delete('learning_tab');
            } else {
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
