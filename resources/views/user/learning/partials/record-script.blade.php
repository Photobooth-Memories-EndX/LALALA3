<script>
    // Learning Record: search, filter, dan pagination client-side (jQuery, tanpa
    // AJAX — data <100 baris sudah dirender penuh oleh Blade).
    // Dipanggil dari loadLearningTab() setiap kali partial tab selesai dimuat.
    function initLearningRecord() {
        var $table = $('#learningRecordTable');

        if (!$table.length || $table.data('recordInit')) {
            return;
        }
        $table.data('recordInit', true);

        var $rows       = $table.find('tbody tr.record-row');
        var $noMatch    = $('#recordNoMatchRow');
        var $info       = $('#recordTableInfo');
        var $pagination = $('#recordPagination');
        var pageLength  = parseInt($('#recordPageLength').val() || '10', 10);
        var currentPage = 1;

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

        function render() {
            var f       = filterState();
            var matched = $rows.filter(function () { return rowMatches($(this), f); });

            $rows.hide();

            var total = matched.length;
            var pages = Math.max(1, Math.ceil(total / pageLength));
            if (currentPage > pages) currentPage = pages;

            var start = (currentPage - 1) * pageLength;
            matched.slice(start, start + pageLength).each(function (i) {
                $(this).show().find('.record-row-index').text(start + i + 1);
            });

            $noMatch.toggleClass('d-none', total > 0);

            $info.text(total > 0
                ? 'Showing ' + (start + 1) + ' to ' + Math.min(start + pageLength, total) + ' of ' + total + ' entries'
                : 'Showing 0 entries');

            buildPagination(pages);
        }

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

        // Tooltip badge Playlist pada kolom Class.
        if (window.bootstrap && bootstrap.Tooltip) {
            $table.find('[data-bs-toggle="tooltip"]').each(function () {
                new bootstrap.Tooltip(this);
            });
        }

        render();
    }
</script>
