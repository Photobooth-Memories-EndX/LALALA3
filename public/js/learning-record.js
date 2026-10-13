/**
 * Learning Record — tabel client-side (HTML Table + jQuery, tanpa AJAX,
 * tanpa DataTables/Yajra).
 *
 * Data <100 baris dirender penuh oleh Blade (x-learning.record-section);
 * file ini mengatur state URL/presentation: search, filter, sorting,
 * dan pagination. Filter GET utama juga diterapkan oleh Laravel.
 * initLearningRecord() dipanggil dari loadLearningTab()
 * (dashboard-script) setiap kali partial tab selesai dimuat.
 *
 * Content dan Playlist adalah dua entity dengan business logic berbeda
 * (progress, assessment, certificate) — keduanya dipisah menjadi dua mode
 * tampilan lewat Quick Filter dan tidak pernah difilter bersamaan.
 */
function initLearningRecordOnline() {
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

    function filterInputValue(selector, fallback) {
        var value = $(selector).val();
        return value === undefined || value === null || value === '' ? fallback : String(value);
    }

    function filterState() {
        return {
            search:         ($('#recordSearch').val() || '').toLowerCase().trim(),
            type:           filterInputValue('#recordFilterType, #hidden_record_filter_type', 'all'),
            modalTypes:     checkedValues('recordModalType[]'),
            program:        checkedValue('recordModalProgram', 'all'),
            classification: filterInputValue('#recordFilterClassification, #hidden_record_filter_classification', 'all'),
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
            year:           filterInputValue('#recordFilterYear, #hidden_record_filter_year', 'all'),
            month:          filterInputValue('#recordFilterMonth, #hidden_record_filter_month', 'all'),
            certificate:    checkedValue('recordModalCertificate', 'all')
        };
    }

    var recordScalarParams = {
        type: 'record_filter_type',
        classification: 'record_filter_classification',
        playlist: 'record_filter_playlist',
        program: 'record_filter_program',
        learningHour: 'record_filter_learning_hour',
        duration: 'record_filter_duration',
        activitySort: 'record_filter_activity_sort',
        completion: 'record_filter_completion',
        modalYear: 'record_filter_modal_year',
        scoreTest: 'record_filter_score',
        year: 'record_filter_year',
        month: 'record_filter_month',
        certificate: 'record_filter_certificate'
    };
    var recordArrayParams = {
        modalTypes: 'record_filter_modal_type',
        learningStatus: 'record_filter_learning_status',
        progressRanges: 'record_filter_progress',
        modalMonths: 'record_filter_modal_month'
    };

    function queryArray(params, name) {
        return params.getAll(name + '[]').concat(params.getAll(name)).filter(function (value) {
            return value !== '' && value !== 'all';
        });
    }

    function setCheckedValues(name, values, defaultValue) {
        var $inputs = $('input[name="' + name + '"]');
        $inputs.prop('checked', false);
        values.forEach(function (value) {
            $inputs.filter(function () {
                return String($(this).val()) === String(value);
            }).prop('checked', true);
        });
        if (!$inputs.filter(':checked').length && defaultValue !== undefined) {
            $inputs.filter(function () {
                return String($(this).val()) === String(defaultValue);
            }).prop('checked', true);
        }
    }

    function restoreRecordFiltersFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var searchVal = params.get('record_search') || params.get('search') || '';
        $('#recordSearch').val(searchVal);
        var requestedSort = String(params.get('record_sort') || '');
        var requestedDirection = String(params.get('record_sort_direction') || '');
        if ($table.find('th[data-sort-key="' + requestedSort + '"]').length
            && (requestedDirection === 'asc' || requestedDirection === 'desc')) {
            sortKey = requestedSort;
            sortDir = requestedDirection;
        }

        var urlQuick = params.get('record_quick') || params.get('quickFilter');
        if (urlQuick && ['all', 'content', 'playlist'].indexOf(urlQuick) !== -1) {
            setQuickPill(urlQuick);
        }

        var urlType = params.get('record_filter_type') || params.get('type');
        if (urlType) {
            $('#recordFilterType, #hidden_record_filter_type').val(urlType);
            var $typeItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_type"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v.toLowerCase() === urlType.toLowerCase();
            });
            if ($typeItem.length) updateQuickDropdown($typeItem);
        }

        var urlClassification = params.get('record_filter_classification') || params.get('classification');
        if (urlClassification) {
            $('#recordFilterClassification, #hidden_record_filter_classification').val(urlClassification);
            var $classItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_classification"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v.toLowerCase() === urlClassification.toLowerCase();
            });
            if ($classItem.length) updateQuickDropdown($classItem);
        }

        var urlYear = params.get('record_filter_year') || params.get('year') || params.get('record_filter_modal_year');
        if (urlYear) {
            $('#recordFilterYear, #hidden_record_filter_year').val(urlYear);
            var $yearItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_year"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v === String(urlYear);
            });
            if ($yearItem.length) updateQuickDropdown($yearItem);
        }

        var urlMonth = params.get('record_filter_month') || params.get('month');
        if (urlMonth) {
            $('#recordFilterMonth, #hidden_record_filter_month').val(urlMonth);
            var $monthItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_month"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v === String(urlMonth);
            });
            if ($monthItem.length) updateQuickDropdown($monthItem);
        }

        setCheckedValues('recordModalType[]', queryArray(params, recordArrayParams.modalTypes), 'all');
        setCheckedValues('recordModalLearningStatus[]', queryArray(params, recordArrayParams.learningStatus));
        setCheckedValues('recordModalProgress[]', queryArray(params, recordArrayParams.progressRanges));
        setCheckedValues('recordModalMonth[]', queryArray(params, recordArrayParams.modalMonths));

        [
            ['recordModalProgram', recordScalarParams.program, 'all'],
            ['recordModalLearningHour', recordScalarParams.learningHour, 'all'],
            ['recordModalDuration', recordScalarParams.duration, 'all'],
            ['recordModalActivitySort', recordScalarParams.activitySort, 'all'],
            ['recordModalCompletion', recordScalarParams.completion, 'all'],
            ['recordModalYear', recordScalarParams.modalYear, 'all'],
            ['recordModalScoreTest', recordScalarParams.scoreTest, 'all'],
            ['recordModalCertificate', recordScalarParams.certificate, 'all']
        ].forEach(function (config) {
            setCheckedValues(config[0], params.has(config[1]) ? [params.get(config[1])] : [], config[2]);
        });
    }

    function buildRecordFilterParams() {
        var params = new URLSearchParams(window.location.search);
        Array.from(params.keys()).forEach(function (key) {
            if (key.indexOf('record_') === 0) {
                params.delete(key);
            }
        });

        var state = filterState();
        var search = $.trim($('#recordSearch').val());
        if (search) {
            params.set('record_search', search);
        }

        Object.keys(recordScalarParams).forEach(function (key) {
            var value = String(state[key] || '');
            if (value !== '' && value !== 'all') {
                params.set(recordScalarParams[key], value);
            }
        });

        Object.keys(recordArrayParams).forEach(function (key) {
            (state[key] || []).forEach(function (value) {
                if (value !== '' && value !== 'all') {
                    params.append(recordArrayParams[key] + '[]', value);
                }
            });
        });

        var activeYear = (state.modalYear && state.modalYear !== 'all') ? state.modalYear : (state.year && state.year !== 'all' ? state.year : null);
        if (activeYear) {
            params.set('record_filter_year', activeYear);
            params.set('record_filter_modal_year', activeYear);
        }

        if (quickFilter && quickFilter !== 'all') {
            params.set('record_quick', quickFilter);
        }

        params.delete('record_page');
        if (sortKey && (sortDir === 'asc' || sortDir === 'desc')) {
            params.set('record_sort', sortKey);
            params.set('record_sort_direction', sortDir);
        }
        params.set('learning_tab', 'record');
        return params;
    }

    function navigateRecordFilters(params) {
        var query = params.toString();
        $('#recordMoreFiltersModal').modal('hide');
        window.location.assign(window.location.pathname + (query ? '?' + query : ''));
    }

    function updateExportUrl() {
        var state = filterState();
        var params = new URLSearchParams();
        Object.keys(state).forEach(function (key) {
            if (Array.isArray(state[key])) {
                state[key].forEach(function (value) {
                    params.append(key + '[]', value);
                    if (recordArrayParams[key]) {
                        params.append(recordArrayParams[key] + '[]', value);
                    }
                });
            } else if (state[key] !== 'all' && state[key] !== '') {
                params.set(key, state[key]);
                if (recordScalarParams[key]) {
                    params.set(recordScalarParams[key], state[key]);
                }
            }
        });
        if (state.search) {
            params.set('search', state.search);
            params.set('record_search', state.search);
        }
        if (quickFilter && quickFilter !== 'all') {
            params.set('quickFilter', quickFilter);
            params.set('record_quick', quickFilter);
        }
        if (sortKey && (sortDir === 'asc' || sortDir === 'desc')) {
            params.set('record_sort', sortKey);
            params.set('record_sort_direction', sortDir);
        }
        var baseUrl = $('#recordDownloadExcel').data('export-url') || '/my-learning/learning-record/export';
        $('#recordDownloadExcel').attr('href', baseUrl + (params.toString() ? '?' + params.toString() : ''));
    }

    function updateSummaryCards(matched) {
        var courseCount = 0;
        var playlistCount = 0;
        var totalHours = 0;

        $.each(matched, function (_, tr) {
            var $tr = $(tr);
            var kind = $tr.attr('data-kind') || $tr.data('kind');
            if (kind === 'content') {
                courseCount++;
            } else if (kind === 'playlist') {
                playlistCount++;
            }
            var lh = parseFloat($tr.attr('data-learning-hour') || $tr.attr('data-sort-learning-hour') || 0);
            if (!isNaN(lh) && lh > 0) {
                totalHours += lh;
            }
        });

        var formattedHours = (Math.round(totalHours * 100) / 100).toFixed(2);

        $('[data-record-stat="hours"], [data-learning-hero="record"] .learning-hero-ds-card--primary .learning-hero-ds-value')
            .text(formattedHours);

        $('[data-record-stat="course"], [data-learning-hero="record"] .learning-hero-ds-card--secondary .learning-hero-ds-value')
            .text(courseCount.toLocaleString());

        $('[data-record-stat="playlist"], [data-learning-hero="record"] .learning-hero-ds-card--success .learning-hero-ds-value')
            .text(playlistCount.toLocaleString());
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

        var rowType = String(d.type || '').toLowerCase();
        var rowKind = String(d.kind || '').toLowerCase();
        return selectedTypes.some(function (type) {
            var t = String(type).toLowerCase();
            if (t === 'playlist') return rowKind === 'playlist';
            if (t === 'content' || t === 'course') return rowKind === 'content';
            if (t === 'video') return rowType === 'video' || rowType === 'youtube';
            if (t === 'document') return rowType === 'document';
            if (t === 'mooc') return rowType === 'mooc' || rowType === 'youtube';
            return rowType === t;
        });
    }

    function resolveMonthNumber(val) {
        if (!val || val === 'all') return null;
        var str = String(val).trim().toLowerCase();
        var n = parseInt(str, 10);
        if (!isNaN(n) && n >= 1 && n <= 12) return n;
        var months = {
            january: 1, jan: 1,
            february: 2, feb: 2,
            march: 3, mar: 3,
            april: 4, apr: 4,
            may: 5,
            june: 6, jun: 6,
            july: 7, jul: 7,
            august: 8, aug: 8,
            september: 9, sep: 9, sept: 9,
            october: 10, oct: 10,
            november: 11, nov: 11,
            december: 12, dec: 12
        };
        return months[str] || null;
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

        if (f.year !== 'all' && String(d.year) !== String(f.year)) return false;
        if (f.modalYear !== 'all' && String(d.year) !== String(f.modalYear)) return false;
        if (f.month !== 'all') {
            var targetMonth = resolveMonthNumber(f.month);
            if (targetMonth !== null && parseInt(d.month, 10) !== targetMonth) return false;
        }
        if (f.modalMonths.length) {
            var targetModalMonths = f.modalMonths.map(resolveMonthNumber).filter(Boolean);
            if (targetModalMonths.length && targetModalMonths.indexOf(parseInt(d.month, 10)) === -1) return false;
        }
        if (f.duration !== 'all' && !rangeMatches(durationSeconds, f.duration)) return false;
        if (f.learningHour !== 'all' && !rangeMatches(learningHour, f.learningHour)) return false;
        if (f.program !== 'all' && String(d.learningProgram) !== f.program) return false;
        if (f.learningStatus.length) {
            var rowStatuses = String(d.learningStatus || '').toLowerCase().split(/\s+/);
            var matchesStatus = f.learningStatus.some(function (st) {
                return rowStatuses.indexOf(String(st).toLowerCase()) !== -1;
            });
            if (!matchesStatus) return false;
        }
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
        var rowType = String(d.type || '').toLowerCase();
        var rowKind = String(d.kind || '').toLowerCase();
        var filterType = String(f.type || '').toLowerCase();

        if (rowKind !== 'content') return false;
        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (filterType !== 'all' && filterType !== '' && filterType !== 'content' && filterType !== 'course') {
            if (filterType === 'video' && !(rowType === 'video' || rowType === 'youtube')) return false;
            if (filterType === 'document' && rowType !== 'document') return false;
            if (filterType === 'mooc' && !(rowType === 'mooc' || rowType === 'youtube')) return false;
            if (filterType !== 'video' && filterType !== 'document' && filterType !== 'mooc' && rowType !== filterType) return false;
        }
        if (f.classification !== 'all' && String(d.classification).toLowerCase() !== String(f.classification).toLowerCase()) return false;

        return commonMatches($row, d, f);
    }

    // Mode Playlist: filter Playlist memilih satu playlist tertentu —
    // baris Content tidak pernah ikut dievaluasi.
    function playlistMatches($row, f) {
        var d = $row.data();
        var rowKind = String(d.kind || '').toLowerCase();

        if (rowKind !== 'playlist') return false;
        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (f.playlist !== 'all' && String(d.playlists || '').split(',').indexOf(f.playlist) === -1) {
            return false;
        }

        return commonMatches($row, d, f);
    }

    function allMatches($row, f) {
        var d = $row.data();
        var rowType = String(d.type || '').toLowerCase();
        var rowKind = String(d.kind || '').toLowerCase();
        var filterType = String(f.type || '').toLowerCase();

        if (!selectedTypeMatches(d, f.modalTypes)) return false;
        if (filterType !== 'all' && filterType !== '') {
            if (filterType === 'playlist' && rowKind !== 'playlist') return false;
            if ((filterType === 'content' || filterType === 'course') && rowKind !== 'content') return false;
            if (filterType === 'video' && !(rowType === 'video' || rowType === 'youtube')) return false;
            if (filterType === 'document' && rowType !== 'document') return false;
            if (filterType !== 'playlist' && filterType !== 'content' && filterType !== 'course' && filterType !== 'video' && filterType !== 'document') {
                if (rowType !== filterType) return false;
            }
        }
        if (f.classification !== 'all' && String(d.classification).toLowerCase() !== String(f.classification).toLowerCase()) return false;

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

        updateSummaryCards(matched);
        updateExportUrl();

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

    function quickDropdownLabel(param, value, text) {
        if (value === 'all') {
            return 'All';
        }

        return text;
    }

    function updateQuickDropdown($item) {
        var value = String($item.attr('data-value') !== undefined ? $item.attr('data-value') : ($item.data('value') || 'all'));
        var label = quickDropdownLabel(String($item.attr('data-param') !== undefined ? $item.attr('data-param') : ($item.data('param') || '')), value, $.trim($item.text()));
        var $dropdown = $item.closest('.dropdown');
        var $button = $dropdown.find('.dropdown-toggle');

        $dropdown.find('.record-quick-item').removeClass('active');
        $item.addClass('active');
        $button.toggleClass('active', value !== 'all');
        $button.find('.fw-semibold').text(label);
    }

    function resetQuickDropdowns() {
        $('.learning-quick-dropdowns .dropdown').each(function () {
            var $allItem = $(this).find('.record-quick-item[data-value="all"]').first();
            if ($allItem.length) {
                updateQuickDropdown($allItem);
            }
        });
    }

    function syncQuickDropdownToModal(param, value) {
        if (param === 'record_filter_type') {
            var $typeInputs = $('#recordMoreFilters input[name="recordModalType[]"]');

            $typeInputs.prop('checked', false);
            $typeInputs.filter('[value="' + value + '"]').prop('checked', true);

            if (!$typeInputs.filter(':checked').length) {
                $typeInputs.filter('[value="all"]').prop('checked', true);
            }
        }

        if (param === 'record_filter_year') {
            $('#recordMoreFilters input[name="recordModalYear"][value="' + value + '"]').prop('checked', true);
        }

        if (param === 'record_filter_month') {
            $('#recordMoreFilters input[name="recordModalMonth[]"]').prop('checked', false);

            if (value !== 'all') {
                $('#recordMoreFilters input[name="recordModalMonth[]"][value="' + value + '"]').prop('checked', true);
            }
        }
    }

    function syncModalToQuickDropdown(param, value) {
        if (param === 'record_filter_year') {
            $('#hidden_record_filter_year, #recordFilterYear').val(value);
            var $yearItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_year"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v === String(value);
            });
            if ($yearItem.length) updateQuickDropdown($yearItem);
        }

        if (param === 'record_filter_month') {
            $('#hidden_record_filter_month, #recordFilterMonth').val(value);
            var $monthItem = $('.learning-quick-dropdowns .record-quick-item[data-param="record_filter_month"]').filter(function () {
                var v = String($(this).attr('data-value') !== undefined ? $(this).attr('data-value') : ($(this).data('value') || ''));
                return v === String(value);
            });
            if ($monthItem.length) updateQuickDropdown($monthItem);
        }
    }

    function resetRecordFilters() {
        $('#recordSearch').val('');
        $('#recordFilterBar select[data-record-filter]').val('all');
        $('#hidden_record_filter_type, #hidden_record_filter_classification, #hidden_record_filter_year, #hidden_record_filter_month').val('all');
        resetQuickDropdowns();
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
            updateExportUrl();
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
            $('#recordFilterType, #recordFilterClassification, #hidden_record_filter_type, #hidden_record_filter_classification').val('all');
        } else if (value === 'content') {
            $('#recordFilterPlaylist').val('all');
        }

        applyModeVisibility(true);
        currentPage = 1;
        updateFilterBadge();
        updateExportUrl();
        animateRows();
        render();
    });

    // Buka/tutup panel More Filters (slide ±250 ms, tanpa request server).
    $('.record-quick-item').on('click', function (e) {
        e.preventDefault();

        var $item = $(this);
        var param = String($item.attr('data-param') !== undefined ? $item.attr('data-param') : ($item.data('param') || ''));
        var value = String($item.attr('data-value') !== undefined ? $item.attr('data-value') : ($item.data('value') || 'all'));

        $('#hidden_' + param).val(value);
        updateQuickDropdown($item);
        syncQuickDropdownToModal(param, value);

        currentPage = 1;
        updateFilterBadge();
        updateExportUrl();
        animateRows();
        render();
    });

    $('#recordMoreFiltersModal').on('shown.bs.modal', function () {
        $('#recordFilterType').trigger('focus');
    });

    // Commit every visible filter through a full-page GET navigation.
    $('#recordApplyFilter, .record-apply-filter').on('click', function () {
        navigateRecordFilters(buildRecordFilterParams());
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
        if ($(this).attr('name') === 'recordModalYear') {
            syncModalToQuickDropdown('record_filter_year', $(this).val());
        }
        if ($(this).attr('name') === 'recordModalMonth[]') {
            var checkedMonths = checkedValues('recordModalMonth[]');
            var targetMonth = checkedMonths.length === 1 ? checkedMonths[0] : 'all';
            syncModalToQuickDropdown('record_filter_month', targetMonth);
        }
        currentPage = 1;
        updateFilterBadge();
        updateExportUrl();
        render();
    });

    // Reset: kosongkan search + seluruh filter lanjutan. Mode aktif
    // dipertahankan — Quick Filter adalah mode tampilan, bukan filter.
    $('#recordClearFilter, .record-clear-filter').on('click', function () {
        resetRecordFilters();
        var params = new URLSearchParams(window.location.search);
        Array.from(params.keys()).forEach(function (key) {
            if (key.indexOf('record_') === 0) {
                params.delete(key);
            }
        });
        params.set('learning_tab', 'record');
        navigateRecordFilters(params);
    });

    // Card "Certificates Ready" (header): terapkan filter Certificate Ready
    // pada mode aktif lalu scroll halus ke tabel — baris pertama yang tampil
    // pasti punya tombol Generate Certificate.
    $('#recordCertReadyCard').on('click', function () {
        $('#recordFilterCertificate').val('ready');
        currentPage = 1;
        updateFilterBadge();
        updateExportUrl();
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

    // ── Category Hover/Click Preview (Popup) ────────────────────────────────

    function initCourseHoverPreview() {
        var $popup = $('#learningRecordCoursePopup');
        if (!$popup.length) {
            $popup = $('<div id="learningRecordCoursePopup" class="learning-record-hover-popup" style="display:none;"></div>').appendTo('body');
        }

        var hideTimer = null;

        function showPopup(trigger) {
            clearTimeout(hideTimer);
            var $trigger = $(trigger);
            var $cell = $trigger.closest('.learning-record-category');
            var $template = $cell.find('.course-preview-template');
            if (!$template.length) {
                $template = $trigger.closest('tr').find('.course-preview-template');
            }

            if (!$template.length) {
                return;
            }

            var htmlContent = $.trim($template.html());
            if (!htmlContent) {
                return;
            }

            $popup.data('activeTrigger', trigger);

            // Set fixed offscreen positioning first to measure intrinsic dimensions accurately
            $popup.html(htmlContent).stop(true, true).css({
                position: 'fixed',
                top: '0px',
                left: '0px',
                right: 'auto',
                bottom: 'auto',
                display: 'block',
                visibility: 'hidden',
                opacity: 0
            });

            var rect = trigger.getBoundingClientRect();
            var popupWidth = $popup.outerWidth();
            var popupHeight = $popup.outerHeight();
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || $(window).width();
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || $(window).height();

            var gap = 6;
            var edge = 12;

            // Anchor horizontally: center popup with trigger badge
            var triggerCenterX = rect.left + (rect.width / 2);
            var left = triggerCenterX - (popupWidth / 2);

            // Constrain left within viewport edges
            if (left + popupWidth > viewportWidth - edge) {
                left = viewportWidth - popupWidth - edge;
            }
            if (left < edge) {
                left = edge;
            }

            // Anchor vertically: prefer below, flip above if space below is tight and more space above
            var spaceBelow = viewportHeight - rect.bottom;
            var spaceAbove = rect.top;
            var top;

            if (spaceBelow < popupHeight + gap + edge && spaceAbove > spaceBelow) {
                top = rect.top - popupHeight - gap;
            } else {
                top = rect.bottom + gap;
            }

            // Constrain top within viewport edges so popup never overflows outside the screen
            if (top + popupHeight > viewportHeight - edge) {
                top = Math.max(edge, viewportHeight - popupHeight - edge);
            }
            if (top < edge) {
                top = edge;
            }

            $popup.css({
                position: 'fixed',
                top: Math.round(top) + 'px',
                left: Math.round(left) + 'px',
                right: 'auto',
                bottom: 'auto',
                visibility: 'visible'
            }).animate({ opacity: 1 }, 120);
        }

        function hidePopup() {
            clearTimeout(hideTimer);
            $popup.data('activeTrigger', null);
            $popup.stop(true, true).fadeOut(120, function () {
                $popup.empty();
            });
        }

        function scheduleHide() {
            hideTimer = setTimeout(function () {
                hidePopup();
            }, 250); // 250ms grace period allows seamless movement from trigger to popup
        }

        $table.off('.categoryPreview')
            .on('mouseenter.categoryPreview', '.category-preview-trigger', function () {
                showPopup(this);
            })
            .on('mouseleave.categoryPreview', '.category-preview-trigger', function () {
                scheduleHide();
            })
            .on('click.categoryPreview', '.category-preview-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if ($popup.is(':visible') && $popup.data('activeTrigger') === this) {
                    hidePopup();
                } else {
                    showPopup(this);
                }
            });

        $popup.off('.categoryPreview')
            .on('mouseenter.categoryPreview', function () {
                clearTimeout(hideTimer);
            })
            .on('mouseleave.categoryPreview', function () {
                scheduleHide();
            });

        $(document).off('click.categoryPreviewDoc').on('click.categoryPreviewDoc', function (e) {
            if ($popup.is(':visible') && !$(e.target).closest('#learningRecordCoursePopup, .category-preview-trigger').length) {
                hidePopup();
            }
        });

        function handleScrollEvent(e) {
            if (!$popup.is(':visible')) return;
            // If scroll originates inside the popup body (e.g. user scrolling the playlist item list), keep popup open
            if (e.target && $(e.target).closest('#learningRecordCoursePopup').length) {
                return;
            }
            hidePopup();
        }

        window.removeEventListener('scroll', handleScrollEvent, true);
        window.addEventListener('scroll', handleScrollEvent, true);

        $(window).off('resize.categoryPreview').on('resize.categoryPreview', function () {
            if ($popup.is(':visible')) {
                hidePopup();
            }
        });

        $(document).off('keydown.categoryPreview').on('keydown.categoryPreview', function (e) {
            if (e.key === 'Escape') {
                hidePopup();
            }
        });
    }

    function initBlockedContentModal() {
        $(document).off('click.learningRecordBlocked', '.learning-record-blocked-trigger')
            .on('click.learningRecordBlocked', '.learning-record-blocked-trigger', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $el = $(this);
                var status = String($el.data('status') || 'INACTIVE').toUpperCase();
                var message = $el.data('message') || (status === 'EXPIRED' ? 'This content period has expired.' : 'This content is currently inactive.');
                var title = $el.data('title') || 'Course';

                var $modal = $('#learningRecordBlockedModal');
                if ($modal.length) {
                    $modal.find('.blocked-modal-title').text(status === 'EXPIRED' ? 'Content Period Expired' : 'Content Inactive');
                    $modal.find('.blocked-course-title').text(title);
                    $modal.find('.blocked-modal-message').text(message);

                    if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                        $modal.modal('show');
                    } else if (window.bootstrap && window.bootstrap.Modal) {
                        var instance = window.bootstrap.Modal.getInstance($modal[0]) || new window.bootstrap.Modal($modal[0]);
                        instance.show();
                    } else {
                        $modal.addClass('show').css('display', 'block');
                        $('body').addClass('modal-open');
                    }
                } else {
                    alert(message);
                }
            });
    }

    restoreRecordFiltersFromUrl();
    applyModeVisibility(false);
    updateFilterBadge();
    if (sortKey && sortDir) {
        applySort();
        updateSortIcons();
    }
    updateExportUrl();
    initCourseHoverPreview();
    initBlockedContentModal();
    render();
}

/**
 * Standardized Client-side Pagination for Non KP OWL (Offline) & All Record tables.
 * Shares identical behavior, page size options (10, 25, 50), button styling, and layout with KP OWL.
 */
function initStandardRecordPagination(tableSelector, pageLengthSelector, infoSelector, paginationSelector) {
    var $table = $(tableSelector);
    if (!$table.length || $table.data('recordInit')) {
        return;
    }
    $table.data('recordInit', true);

    var $tbody = $table.find('tbody');
    var $rows = $tbody.find('tr.record-row');
    var $pageLength = $(pageLengthSelector);
    var $info = $(infoSelector);
    var $pagination = $(paginationSelector);

    var pageLength = parseInt($pageLength.val() || '10', 10);
    var currentPage = 1;

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

        if (pages <= 7) {
            for (var p = 1; p <= pages; p++) {
                item(String(p), p, false, p === currentPage);
            }
        } else {
            // First page
            item('1', 1, false, currentPage === 1);

            if (currentPage > 4) {
                $pagination.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }

            var startPage = Math.max(2, currentPage - 1);
            var endPage = Math.min(pages - 1, currentPage + 1);

            if (currentPage <= 4) {
                endPage = 4;
            }
            if (currentPage >= pages - 3) {
                startPage = pages - 3;
            }

            for (var i = startPage; i <= endPage; i++) {
                item(String(i), i, false, i === currentPage);
            }

            if (currentPage < pages - 3) {
                $pagination.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }

            // Last page
            item(String(pages), pages, false, currentPage === pages);
        }

        item('&raquo;', currentPage + 1, currentPage === pages, false);
    }

    function render() {
        var total = $rows.length;
        var pages = Math.max(1, Math.ceil(total / pageLength));
        if (currentPage > pages) currentPage = pages;
        if (currentPage < 1) currentPage = 1;

        $rows.hide();

        var start = (currentPage - 1) * pageLength;
        var end = Math.min(start + pageLength, total);

        $rows.slice(start, end).show().each(function (i) {
            $(this).find('.record-row-index').text(start + i + 1);
        });

        if (total > 0) {
            $info.text('Showing ' + (start + 1) + ' to ' + end + ' of ' + total + ' entries');
        } else {
            $info.text('Showing 0 to 0 of 0 entries');
        }

        buildPagination(pages);
    }

    $pageLength.on('change', function () {
        pageLength = parseInt($(this).val(), 10) || 10;
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

function initLearningRecordOffline() {
    initStandardRecordPagination(
        '#learningRecordOfflineTable',
        '#recordOfflinePageLength',
        '#recordOfflineTableInfo',
        '#recordOfflinePagination'
    );
}

function initLearningRecordAll() {
    initStandardRecordPagination(
        '#learningRecordAllTable',
        '#recordAllPageLength',
        '#recordAllTableInfo',
        '#recordAllPagination'
    );
}

function initLearningRecord() {
    initLearningRecordOnline();
    initLearningRecordOffline();
    initLearningRecordAll();
}
