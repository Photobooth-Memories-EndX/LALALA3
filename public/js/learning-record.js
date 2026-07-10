/**
 * Learning Record — tabel client-side (HTML Table + jQuery, tanpa AJAX,
 * tanpa DataTables/Yajra).
 *
 * Data <100 baris dirender penuh oleh Blade (x-learning.record-section);
 * file ini hanya mengatur presentation layer: search, filter, sorting,
 * dan pagination. initLearningRecord() dipanggil dari loadLearningTab()
 * (dashboard-script) setiap kali partial tab selesai dimuat.
 */
function initLearningRecord() {
    var $table = $('#learningRecordTable');

    if (!$table.length || $table.data('recordInit')) {
        return;
    }
    $table.data('recordInit', true);

    var $tbody      = $table.find('tbody');
    // Urutan tampil baris — array biasa agar mudah disusun ulang saat sorting.
    var rowOrder    = $tbody.find('tr.record-row').toArray();
    var $noMatch    = $('#recordNoMatchRow');
    var $info       = $('#recordTableInfo');
    var $pagination = $('#recordPagination');
    var pageLength  = parseInt($('#recordPageLength').val() || '10', 10);
    var currentPage = 1;
    var sortKey     = null;
    var sortDir     = null;

    // ── Filter ──────────────────────────────────────────────────────────────

    function filterState() {
        return {
            search:         ($('#recordSearch').val() || '').toLowerCase().trim(),
            type:           $('#recordFilterType').val() || 'all',
            classification: $('#recordFilterClassification').val() || 'all',
            playlist:       $('#recordFilterPlaylist').val() || 'all',
            year:           $('#recordFilterYear').val() || 'all',
            month:          $('#recordFilterMonth').val() || 'all',
            certificate:    $('#recordFilterCertificate').val() || 'all'
        };
    }

    // Replika aturan filter server-side lama (LearningRecordController):
    //  - "playlist" adalah pseudo-type — hanya baris Playlist yang tampil.
    //  - Filter type/classification asli menyembunyikan baris Playlist.
    //  - Filter Playlist: baris Playlist terpilih + Content bagian darinya.
    function rowMatches($row, f) {
        var d = $row.data();

        if (f.type !== 'all') {
            if (f.type === 'playlist') {
                if (d.kind !== 'playlist') return false;
            } else if (d.kind !== 'content' || String(d.type) !== f.type) {
                return false;
            }
        }

        if (f.classification !== 'all' && (d.kind !== 'content' || String(d.classification) !== f.classification)) {
            return false;
        }

        if (f.playlist !== 'all') {
            var ids = String(d.playlists || '').split(',');
            if (ids.indexOf(f.playlist) === -1) return false;
        }

        if (f.year !== 'all' && String(d.year) !== f.year) return false;
        if (f.month !== 'all' && String(d.month) !== f.month) return false;
        if (f.certificate !== 'all' && String(d.cert) !== f.certificate) return false;
        if (f.search && String(d.search).indexOf(f.search) === -1) return false;

        return true;
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

        rowOrder.sort(function (a, b) {
            var va  = sortValue(a, sortKey, type);
            var vb  = sortValue(b, sortKey, type);
            var cmp = type === 'text' ? String(va).localeCompare(String(vb)) : va - vb;
            return sortDir === 'desc' ? -cmp : cmp;
        });

        // Susun ulang DOM sesuai urutan baru; baris "no match" tetap paling bawah.
        $.each(rowOrder, function (_, tr) { $tbody.append(tr); });
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
        var matched = rowOrder.filter(function (tr) { return rowMatches($(tr), f); });

        $(rowOrder).hide();

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

    // Multi filter: setiap perubahan dropdown langsung dirender ulang.
    $('#recordFilterBar [data-record-filter]').on('change', function () {
        currentPage = 1;
        render();
    });

    // Clear Filter: reset seluruh input lalu render ulang tabel.
    $('#recordClearFilter').on('click', function () {
        $('#recordSearch').val('');
        $('#recordFilterBar [data-record-filter]').val('all');
        currentPage = 1;
        render();
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

    render();
}
