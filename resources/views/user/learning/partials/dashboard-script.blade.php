@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
{{-- ApexCharts: dimuat di awal agar tersedia saat tab Monitoring (AJAX) dirender. --}}
<script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
{{-- Learning Record: search, filter, sorting & pagination client-side (jQuery, tanpa AJAX). --}}
<script src="{{ asset('js/learning-record.js') }}"></script>

<script>
    var _activeTab = null;

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

    function initDataTables() {
        // #learningRecordTable tidak di sini — Learning Record dirender penuh oleh
        // Blade dan ditangani initLearningRecord() (learning-record.js, tanpa DataTables).
        var tables = ['#assessmentTable', '#assessmentResultTable'];
        tables.forEach(function (id) {
            if ($(id).length && !$.fn.DataTable.isDataTable(id)) {
                $(id).DataTable({ ordering: false, pageLength: 10, autoWidth: false });
            }
        });
    }

    function loadLearningTab(tab) {
        if (_activeTab === tab) return;
        _activeTab = tab;

        // Update active button state
        $('.learning-tab-btn').removeClass('btn-primary').addClass('btn-white');
        $('.learning-tab-btn[data-target="#tab-' + tab + '"]').removeClass('btn-white').addClass('btn-primary');

        // Show skeleton while loading
        $('#learning-tab-content').html(skeletonHtml());

        // Forward current URL query params so filters and pagination work
        var params = new URLSearchParams(window.location.search);
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
            initDataTables();
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

        if (mode === 'playlist') {
            params.set('ongoing_mode', 'playlist');
        }

        var qs = params.toString();
        history.pushState({}, '', window.location.pathname + (qs ? '?' + qs : ''));

        _activeTab = null; // paksa loadLearningTab memuat ulang partial ongoing
        loadLearningTab('ongoing');
    });

    // Back/forward browser: muat ulang partial ongoing sesuai query string URL.
    window.addEventListener('popstate', function () {
        if (_activeTab === 'ongoing') {
            _activeTab = null;
            loadLearningTab('ongoing');
        }
    });

    $(function () {
        // Load ongoing tab automatically on page load
        loadLearningTab('ongoing');

        // Tab button click handler
        $('.learning-tab-btn').on('click', function () {
            var target = $(this).data('target');          // e.g. "#tab-ongoing"
            var tab    = target.replace('#tab-', '');     // → "ongoing"
            loadLearningTab(tab);
        });
    });
</script>
@endsection
