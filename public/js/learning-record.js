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

    function checkedValues(name) {
        return $('input[name="' + name + '"]:checked').map(function () {
            return String($(this).val());
        }).get();
    }

    function checkedValue(name, fallback) {
        return String($('input[name="' + name + '"]:checked').val() || fallback);
    }

    function filterState() {
        return {
            search:         ($('#recordSearch').val() || '').toLowerCase().trim(),
            type:           $('#recordFilterType').val() || 'all',
            modalTypes:     checkedValues('recordModalType[]'),
            program:        checkedValue('recordModalProgram', 'all'),
            classification: $('#recordFilterClassification').val() || 'all',
            playlist:       $('#recordFilterPlaylist').val() || 'all',
            learningHour:   checkedValue('recordModalLearningHour', 'all'),
            duration:       checkedValue('recordModalDuration', 'all'),
            activitySort:   checkedValue('recordModalActivitySort', 'all'),
            completion:     checkedValue('recordModalCompletion', 'all'),
            learningStatus: checkedValues('recordModalLearningStatus[]'),
            progressRanges: checkedValues('recordModalProgress[]'),
            modalYear:      checkedValue('recordModalYear', 'all'),
            modalMonths:    checkedValues('recordModalMonth[]'),
            scoreTest:      checkedValue('recordModalScoreTest', 'all'),
            year:           $('#recordFilterYear').val() || 'all',
            month:          $('#recordFilterMonth').val() || 'all',
            certificate:    checkedValue('recordModalCertificate', 'all')
        };
    }

    function activeRows() {
        if (quickFilter === 'content') return contentRows;
        if (quickFilter === 'playlist') return playlistRows;
        return allRows;
    }

    function rowNumber($row, name, fallback) {
        var raw = $row.attr('data-' + name);
        var n = parseFloat(raw);
        return isNaN(n) ? fallback : n;
    }

    function rangeMatches(value, range) {
        if (range === 'all') return true;
        if (range.indexOf('gt') === 0) return value > parseFloat(range.replace('gt', ''));
        if (range.indexOf('-') === -1) return value === parseFloat(range);

        var parts = range.split('-');
        return value >= parseFloat(parts[0]) && value <= parseFloat(parts[1]);
    }

    function anyRangeMatches(value, ranges) {
        if (!ranges || !ranges.length) return true;
        return ranges.some(function (range) {
            return rangeMatches(value, range);
        });
    }

    function selectedTypeMatches(d, selectedTypes) {
        if (!selectedTypes || !selectedTypes.length || selectedTypes.indexOf('all') !== -1) return true;

        var rowType = String(d.type || '');
        return selectedTypes.some(function (type) {
            if (type === 'Playlist') return String(d.kind) === 'playlist';
            if (type === 'Video') return rowType === 'Video' || rowType === 'YouTube';
            return rowType === type;
        });
    }

    function dateMatches(raw, mode) {
        if (mode === 'all' || mode === 'newest' || mode === 'oldest') return true;
        if (!raw) return false;

        var date = new Date(String(raw).replace(' ', 'T'));
        if (isNaN(date.getTime())) return false;

        var now = new Date();
        var thisMonth = now.getMonth();
        var thisYear = now.getFullYear();
        var lastMonthDate = new Date(thisYear, thisMonth - 1, 1);

        if (mode === 'this_month') {
            return date.getFullYear() === thisYear && date.getMonth() === thisMonth;
        }
        if (mode === 'last_month') {
            return date.getFullYear() === lastMonthDate.getFullYear() && date.getMonth() === lastMonthDate.getMonth();
        }
        if (mode === 'this_year') {
            return date.getFullYear() === thisYear;
        }

        return true;
    }

    // Filter yang berlaku di semua baris: Year, Month, Certificate, Search, dan filter modal.
    function commonMatches($row, d, f) {
        var durationSeconds = rowNumber($row, 'sort-duration', -1);
        var learningHour = rowNumber($row, 'learning-hour', -1);
        var score = rowNumber($row, 'sort-score', -1);
        var progress = rowNumber($row, 'sort-progress', 0);
        var finish = $row.attr('data-sort-finish') || '';

        if (f.year !== 'all' && String(d.year) !== f.year) return false;
        if (f.modalYear !== 'all' && String(d.year) !== f.modalYear) return false;
        if (f.month !== 'all' && String(d.month) !== f.month) return false;
        if (f.modalMonths.length && f.modalMonths.indexOf(String(d.month)) === -1) return false;
        if (f.duration !== 'all' && !rangeMatches(durationSeconds, f.duration)) return false;
        if (f.learningHour !== 'all' && !rangeMatches(learningHour, f.learningHour)) return false;
        if (f.program !== 'all' && String(d.learningProgram) !== f.program) return false;
        if (f.learningStatus.length && f.learningStatus.indexOf(String(d.learningStatus)) === -1) return false;
        if (!anyRangeMatches(progress, f.progressRanges)) return false;
        if (f.completion === 'not_started' && progress !== 0) return false;
        if (f.completion === 'in_progress' && !(progress > 0 && progress <= 75)) return false;
        if (f.completion === 'almost_done' && !(progress >= 76 && progress < 100)) return false;
        if (f.completion === 'completed' && progress !== 100) return false;
        if (f.scoreTest === 'none' && score >= 0) return false;
        if (f.scoreTest === 'lt60' && !(score >= 0 && score < 60)) return false;
        if (f.scoreTest === '60-79' && !(score >= 60 && score <= 79)) return false;
        if (f.scoreTest === '80-100' && !(score >= 80 && score <= 100)) return false;
        if (f.certificate !== 'all' && String(d.cert) !== f.certificate) return false;
        if (f.search && String(d.search).indexOf(f.search) === -1) return false;
        return true;
    }

    // Mode Content: Type (Video/Document) + Classification hanya menyaring
    // baris Content — Playlist tidak pernah ikut dievaluasi.
    function contentMatches($row, f) {
        var d = $row.data();

        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (f.type !== 'all' && String(d.type) !== f.type) return false;
        if (f.classification !== 'all' && String(d.classification) !== f.classification) return false;

        return commonMatches($row, d, f);
    }

    // Mode Playlist: filter Playlist memilih satu playlist tertentu —
    // baris Content tidak pernah ikut dievaluasi.
    function playlistMatches($row, f) {
        var d = $row.data();

        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (f.playlist !== 'all' && String(d.playlists || '').split(',').indexOf(f.playlist) === -1) {
            return false;
        }

        return commonMatches($row, d, f);
    }

    function allMatches($row, f) {
        var d = $row.data();
        var rowType = String(d.type || '');

        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (f.type === 'Playlist' && String(d.kind) !== 'playlist') return false;
        if (f.type === 'Video' && !(rowType === 'Video' || rowType === 'YouTube')) return false;
        if (f.type === 'Document' && rowType !== 'Document') return false;
        if (f.classification !== 'all' && String(d.classification) !== f.classification) return false;

        if (f.playlist !== 'all' && String(d.playlists || '').split(',').indexOf(f.playlist) === -1) {
            return false;
        }

        return commonMatches($row, d, f);
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

        if (f.activitySort === 'newest' || f.activitySort === 'oldest' || f.activitySort === 'recent') {
            matched.sort(function (a, b) {
                var va = $(a).attr('data-sort-finish') || '';
                var vb = $(b).attr('data-sort-finish') || '';
                var cmp = String(va).localeCompare(String(vb));
                return f.activitySort === 'oldest' ? cmp : -cmp;
            });
        }

        if (f.activitySort === 'longest' || f.activitySort === 'shortest') {
            matched.sort(function (a, b) {
                var va = rowNumber($(a), 'sort-duration', -1);
                var vb = rowNumber($(b), 'sort-duration', -1);
                return f.activitySort === 'shortest' ? va - vb : vb - va;
            });
        }

        if (f.completion === 'highest' || f.completion === 'lowest') {
            matched.sort(function (a, b) {
                var va = rowNumber($(a), 'sort-progress', 0);
                var vb = rowNumber($(b), 'sort-progress', 0);
                return f.completion === 'lowest' ? va - vb : vb - va;
            });
        }

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
        $('#recordMoreFilters input[type="radio"][data-record-modal-filter]:checked').each(function () {
            if (String($(this).val()) !== 'all' && String($(this).val()) !== 'newest') count++;
        });
        $('#recordMoreFilters input[type="checkbox"][data-record-modal-filter]:checked').each(function () {
            if (String($(this).val()) !== 'all') count++;
        });
        $('#recordMoreFiltersCount').text(count).toggleClass('d-none', count === 0);
    }

    function closeMoreFilters() {
        $('#recordMoreFiltersModal').modal('hide');
    }

    function resetRecordFilters() {
        $('#recordSearch').val('');
        $('#recordFilterBar select[data-record-filter]').val('all');
        $('#recordMoreFilters input[type="checkbox"][data-record-modal-filter]').prop('checked', false);
        $('#recordMoreFilters input[name="recordModalType[]"][value="all"]').prop('checked', true);
        $('#recordMoreFilters input[type="radio"][value="all"]').prop('checked', true);
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
    $('#recordMoreFiltersModal').on('shown.bs.modal', function () {
        $('#recordFilterType').trigger('focus');
    });

    // Apply: filter sudah live saat berubah — tombol ini hanya menutup panel.
    $('#recordApplyFilter, .record-apply-filter').on('click', function () {
        currentPage = 1;
        render();
        closeMoreFilters();
    });

    // Multi filter: setiap perubahan kontrol langsung dirender ulang.
    $('#recordFilterBar [data-record-filter], #recordMoreFilters [data-record-modal-filter]').on('change', function () {
        if ($(this).attr('name') === 'recordModalType[]') {
            if ($(this).val() === 'all' && this.checked) {
                $('#recordMoreFilters input[name="recordModalType[]"]').not(this).prop('checked', false);
            } else if ($(this).val() !== 'all' && this.checked) {
                $('#recordMoreFilters input[name="recordModalType[]"][value="all"]').prop('checked', false);
            }
        }
        currentPage = 1;
        updateFilterBadge();
        render();
    });

    // Reset: kosongkan search + seluruh filter lanjutan. Mode aktif
    // dipertahankan — Quick Filter adalah mode tampilan, bukan filter.
    $('#recordClearFilter, .record-clear-filter').on('click', function () {
        resetRecordFilters();
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
