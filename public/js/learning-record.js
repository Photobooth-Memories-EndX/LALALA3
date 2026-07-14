/**
 * Learning Record — tabel client-side (HTML Table + jQuery, tanpa AJAX,
 * tanpa DataTables/Yajra).
 *
 * Data <100 baris dirender penuh oleh Blade (x-learning.record-section);
 * file ini hanya mengatur presentation layer: search, filter, sorting,
 * dan pagination. initLearningRecord() dipanggil dari loadLearningTab()
 * (dashboard-script) setiap kali partial tab selesai dimuat.
 *
 * Content dan Playlist adalah dua entity dengan business logic berbeda
 * (progress, assessment, certificate) — keduanya dipisah menjadi dua mode
 * tampilan lewat Quick Filter dan tidak pernah difilter bersamaan.
 */
function initLearningRecord() {
    var $table = $('#learningRecordTable');

    if (!$table.length || $table.data('recordInit')) {
        return;
    }
    $table.data('recordInit', true);

    var $tbody = $table.find('tbody');

    // Collection dipisah sejak awal (bukan satu array + if/else kind) —
    // Quick Filter hanya menentukan collection mana yang dirender.
    var allRows      = $tbody.find('tr.record-row').toArray();
    var contentRows  = allRows.filter(function (tr) { return $(tr).data('kind') === 'content'; });
    var playlistRows = allRows.filter(function (tr) { return $(tr).data('kind') === 'playlist'; });

    var $noMatch    = $('#recordNoMatchRow');
    var $info       = $('#recordTableInfo');
    var $pagination = $('#recordPagination');
    var pageLength  = parseInt($('#recordPageLength').val() || '10', 10);
    var currentPage = 1;
    var sortKey     = null;
    var sortDir     = null;
    // Mode tampilan aktif: 'content' (Video + Document — default) | 'playlist'.
    var quickFilter = 'all';

    // ── Filter ──────────────────────────────────────────────────────────────

    function filterState() {
        return {
            search:         ($('#recordSearch').val() || '').toLowerCase().trim(),
            type:           $('#recordFilterType').val() || 'all',
            classification: $('#recordFilterClassification').val() || 'all',
            playlist:       $('#recordFilterPlaylist').val() || 'all',
            learningHour:   $('#recordFilterLearningHour').val() || 'all',
            year:           $('#recordFilterYear').val() || 'all',
            month:          $('#recordFilterMonth').val() || 'all',
            certificate:    $('#recordFilterCertificate').val() || 'all'
        };
    }

    function activeRows() {
        if (quickFilter === 'content') return contentRows;
        if (quickFilter === 'playlist') return playlistRows;
        return allRows;
    }

    // Filter yang berlaku di kedua mode: Year, Month, Certificate, Search.
    function commonMatches(d, f) {
        if (f.year !== 'all' && String(d.year) !== f.year) return false;
        if (f.month !== 'all' && String(d.month) !== f.month) return false;
        if (f.learningHour !== 'all' && String(d.learningHour) !== f.learningHour) return false;
        if (f.certificate !== 'all' && String(d.cert) !== f.certificate) return false;
        if (f.search && String(d.search).indexOf(f.search) === -1) return false;
        return true;
    }

    // Mode Content: Type (Video/Document) + Classification hanya menyaring
    // baris Content — Playlist tidak pernah ikut dievaluasi.
    function contentMatches($row, f) {
        var d = $row.data();

        if (f.type !== 'all' && String(d.type) !== f.type) return false;
        if (f.classification !== 'all' && String(d.classification) !== f.classification) return false;

        return commonMatches(d, f);
    }

    // Mode Playlist: filter Playlist memilih satu playlist tertentu —
    // baris Content tidak pernah ikut dievaluasi.
    function playlistMatches($row, f) {
        var d = $row.data();

        if (f.playlist !== 'all' && String(d.playlists || '').split(',').indexOf(f.playlist) === -1) {
            return false;
        }

        return commonMatches(d, f);
    }

    function allMatches($row, f) {
        return $row.data('kind') === 'playlist'
            ? playlistMatches($row, f)
            : contentMatches($row, f);
    }

    // ── Sorting (client-side murni — tanpa request server) ─────────────────

    // "1:05:30" / "05:30" → detik; angka polos dibiarkan; "-" → -1 (paling kecil).
    function durationSeconds(raw) {
        raw = String(raw || '').trim();
        if (!raw || raw === '-') return -1;

        if (raw.indexOf(':') !== -1) {
            return raw.split(':').reduce(function (total, part) {
                return total * 60 + (parseFloat(part) || 0);
            }, 0);
        }

        var n = parseFloat(raw);
        return isNaN(n) ? -1 : n;
    }

    function sortValue(tr, key, type) {
        var raw = $(tr).attr('data-sort-' + key) || '';

        if (type === 'number') {
            var n = parseFloat(raw);
            return isNaN(n) ? -1 : n; // baris tanpa nilai (mis. score "-") di posisi terkecil
        }
        if (type === 'duration') {
            return durationSeconds(raw);
        }
        return raw; // text — sudah lowercase dari Blade
    }

    function applySort() {
        var type = $table.find('th[data-sort-key="' + sortKey + '"]').attr('data-sort-type') || 'text';

        function comparator(a, b) {
            var va  = sortValue(a, sortKey, type);
            var vb  = sortValue(b, sortKey, type);
            var cmp = type === 'text' ? String(va).localeCompare(String(vb)) : va - vb;
            return sortDir === 'desc' ? -cmp : cmp;
        }

        // Kedua collection diurutkan dengan comparator sama agar urutan tetap
        // konsisten saat user berpindah mode.
        allRows.sort(comparator);
        contentRows.sort(comparator);
        playlistRows.sort(comparator);

        // Susun ulang DOM sesuai urutan baru; baris "no match" tetap paling bawah.
        $.each(quickFilter === 'all' ? allRows : contentRows.concat(playlistRows), function (_, tr) { $tbody.append(tr); });
        if ($noMatch.length) $tbody.append($noMatch);
    }

    function updateSortIcons() {
        var $headers = $table.find('th.record-sortable');
        $headers.removeClass('is-sorted').find('.record-sort-icons span').removeClass('active');

        $headers.filter('[data-sort-key="' + sortKey + '"]')
            .addClass('is-sorted')
            .find(sortDir === 'asc' ? '.record-sort-asc' : '.record-sort-desc')
            .addClass('active');
    }

    // Animasi kecil setiap kali urutan berubah.
    var animateTimer = null;
    function animateRows() {
        clearTimeout(animateTimer);
        $tbody.removeClass('record-sort-animate');
        void $tbody[0].offsetWidth; // reflow — restart animasi CSS
        $tbody.addClass('record-sort-animate');
        animateTimer = setTimeout(function () { $tbody.removeClass('record-sort-animate'); }, 400);
    }

    $table.find('th.record-sortable').on('click', function (e) {
        var key = $(this).attr('data-sort-key');

        if ($(e.target).hasClass('record-sort-asc')) {
            sortDir = 'asc';                              // klik ▲ → ascending
        } else if ($(e.target).hasClass('record-sort-desc')) {
            sortDir = 'desc';                             // klik ▼ → descending
        } else if (sortKey === key) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc'; // klik ulang header → toggle
        } else {
            sortDir = 'asc';
        }
        sortKey = key;

        applySort();
        updateSortIcons();
        animateRows();
        render();
    });

    // ── Pagination ──────────────────────────────────────────────────────────

    function buildPagination(pages) {
        $pagination.empty();

        if (pages <= 1) {
            return;
        }

        function item(label, page, disabled, active) {
            var $li = $('<li class="page-item"></li>')
                .toggleClass('disabled', !!disabled)
                .toggleClass('active', !!active);

            $('<a class="page-link" href="#"></a>').html(label).data('page', page).appendTo($li);
            $pagination.append($li);
        }

        item('&laquo;', currentPage - 1, currentPage === 1, false);
        for (var p = 1; p <= pages; p++) {
            item(String(p), p, false, p === currentPage);
        }
        item('&raquo;', currentPage + 1, currentPage === pages, false);
    }

    // ── Render ──────────────────────────────────────────────────────────────

    function render() {
        var f       = filterState();
        var matcher = quickFilter === 'playlist'
            ? playlistMatches
            : (quickFilter === 'content' ? contentMatches : allMatches);
        var matched = activeRows().filter(function (tr) { return matcher($(tr), f); });

        $(allRows).hide();

        var total = matched.length;
        var pages = Math.max(1, Math.ceil(total / pageLength));
        if (currentPage > pages) currentPage = pages;

        var start = (currentPage - 1) * pageLength;
        $.each(matched.slice(start, start + pageLength), function (i, tr) {
            $(tr).show().find('.record-row-index').text(start + i + 1);
        });

        $noMatch.toggleClass('d-none', total > 0);

        $info.text(total > 0
            ? 'Showing ' + (start + 1) + ' to ' + Math.min(start + pageLength, total) + ' of ' + total + ' entries'
            : 'Showing 0 entries');

        buildPagination(pages);
    }

    // ── Quick Filter & More Filters ─────────────────────────────────────────

    function setQuickPill(value) {
        quickFilter = value;
        $('#recordFilterBar [data-record-quick]')
            .removeClass('active')
            .filter('[data-record-quick="' + value + '"]').addClass('active');
    }

    // Tampilkan hanya filter lanjutan yang relevan dengan mode aktif:
    // Type + Classification milik Content, dropdown Playlist milik Playlist.
    function applyModeVisibility(animate) {
        var $show = quickFilter === 'all'
            ? $('#recordMoreFilters [data-record-mode]')
            : $('#recordMoreFilters [data-record-mode="' + quickFilter + '"]');
        var $hide = $('#recordMoreFilters [data-record-mode]').not($show);

        $hide.stop(true, true).hide();
        if (animate) {
            $show.stop(true, true).fadeIn(200);
        } else {
            $show.show();
        }
    }

    // Badge jumlah filter lanjutan aktif pada tombol More Filters —
    // hanya menghitung filter yang relevan dengan mode aktif.
    function updateFilterBadge() {
        var count = 0;
        $('#recordMoreFilters [data-record-filter]').each(function () {
            var mode = $(this).closest('[data-record-mode]').attr('data-record-mode');
            if (mode && quickFilter !== 'all' && mode !== quickFilter) return;
            if (($(this).val() || 'all') !== 'all') count++;
        });
        $('#recordMoreFiltersCount').text(count).toggleClass('d-none', count === 0);
    }

    function closeMoreFilters() {
        $('#recordMoreFiltersToggle').attr('aria-expanded', 'false').removeClass('is-open');
        $('#recordMoreFilters').stop(true, true).slideUp(250);
    }

    // ── Events ──────────────────────────────────────────────────────────────

    // Search realtime dengan debounce — tanpa reload halaman.
    var searchTimer = null;
    $('#recordSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            currentPage = 1;
            render();
        }, 400);
    });

    // Quick Filter: pindah mode Content ⇄ Playlist — murni client-side.
    // Filter milik mode lama di-reset agar tidak diam-diam terbawa ke mode baru.
    $('#recordFilterBar [data-record-quick]').on('click', function () {
        var value = $(this).attr('data-record-quick');
        if (value === quickFilter) return;

        setQuickPill(value);
        if (value === 'playlist') {
            $('#recordFilterType, #recordFilterClassification').val('all');
        } else if (value === 'content') {
            $('#recordFilterPlaylist').val('all');
        }

        applyModeVisibility(true);
        currentPage = 1;
        updateFilterBadge();
        animateRows();
        render();
    });

    // Buka/tutup panel More Filters (slide ±250 ms, tanpa request server).
    $('#recordMoreFiltersToggle').on('click', function () {
        var expanded = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', String(!expanded)).toggleClass('is-open', !expanded);
        $('#recordMoreFilters').stop(true, true).slideToggle(250);
    });

    // Apply: filter sudah live saat berubah — tombol ini hanya menutup panel.
    $('#recordApplyFilter').on('click', function () {
        currentPage = 1;
        render();
        closeMoreFilters();
    });

    // Multi filter: setiap perubahan dropdown langsung dirender ulang.
    $('#recordFilterBar [data-record-filter]').on('change', function () {
        currentPage = 1;
        updateFilterBadge();
        render();
    });

    // Reset: kosongkan search + seluruh filter lanjutan. Mode aktif
    // dipertahankan — Quick Filter adalah mode tampilan, bukan filter.
    $('#recordClearFilter').on('click', function () {
        $('#recordSearch').val('');
        $('#recordFilterBar [data-record-filter]').val('all');
        currentPage = 1;
        updateFilterBadge();
        render();
    });

    // Card "Certificates Ready" (header): terapkan filter Certificate Ready
    // pada mode aktif lalu scroll halus ke tabel — baris pertama yang tampil
    // pasti punya tombol Generate Certificate.
    $('#recordCertReadyCard').on('click', function () {
        $('#recordFilterCertificate').val('ready');
        currentPage = 1;
        updateFilterBadge();
        render();
        $table[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    $('#recordPageLength').on('change', function () {
        pageLength  = parseInt($(this).val(), 10) || 10;
        currentPage = 1;
        render();
    });

    $pagination.on('click', 'a.page-link', function (e) {
        e.preventDefault();

        var $li = $(this).parent();
        if ($li.hasClass('disabled') || $li.hasClass('active')) {
            return;
        }

        currentPage = $(this).data('page');
        render();
    });

    applyModeVisibility(false);
    render();
}
