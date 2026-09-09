{{-- Props: $tableId, $ajaxUrl, $withFilters (bool), $title (optional), $subtitle (optional) --}}
@php
    $withFilters = $withFilters ?? false;
@endphp

<div class="card border border-light-subtle shadow-sm rounded-3 monitoring-table-card">

    <div class="card-header bg-transparent border-0 py-3 px-3 px-md-4">
        <div class="w-100 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h3 class="card-title fw-bold text-dark mb-1">{{ $title ?? 'List User' }}</h3>
                <div class="text-muted small">{{ $subtitle ?? 'Progress belajar user secara real-time.' }}</div>
            </div>
            @unless ($withFilters)
                <div class="mt-2 mt-md-0">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input id="{{ $tableId }}-search" type="text" class="form-control form-control-sm"
                               placeholder="Cari name / NIP...">
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <div class="table-responsive">
        <table id="{{ $tableId }}" class="table table-hover table-vcenter owl-table mb-0" style="width:100%">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th class="text-center">Content Final</th>
                    <th>Average Progress</th>
                    <th>Assessment Terakhir</th>
                    <th class="text-end" style="width: 120px;">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

@push('scripts')
<script>
    $(function () {
        let searchTimer;

        const table = $('#{{ $tableId }}').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            order: [[1, 'asc']],
            dom: "<'row'<'col-sm-12'tr>><'row align-items-center mt-3 px-3 pb-3'<'col-sm-12 col-md-5 text-muted small'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
            ajax: {
                url: @json($ajaxUrl),
                @if ($withFilters)
                data: function (d) {
                    d.division   = $('#filter-division').val();
                    d.department = $('#filter-department').val();
                    d.team       = $('#filter-team').val();
                    d.role       = $('#filter-role').val();
                }
                @endif
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                {data: 'name_link', name: 'name'},
                {data: 'id_emp', name: 'id_emp'},
                {data: 'division', name: 'division'},
                {data: 'department', name: 'department'},
                {data: 'team', name: 'team'},
                {data: 'finished_content_count', name: 'finished_content_count', orderable: false, searchable: false, className: 'text-center'},
                {data: 'progress_badge', name: 'avg_progress', orderable: false, searchable: false},
                {data: 'last_assessment', name: 'last_assessment_at', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'},
            ]
        });

        @if ($withFilters)
        function updateBadgeState() {
            const hasDept = $('#filter-department').val() !== '';
            const hasTeam = $('#filter-team').val() !== '';
            if (hasDept || hasTeam) {
                $('#more-filters-badge').show();
            } else {
                $('#more-filters-badge').hide();
            }
        }

        // Entries select change
        $('#filter-entries').on('change', function () {
            table.page.len(parseInt(this.value, 10)).draw();
        });

        // Quick filter dropdown changes
        $('#filter-division, #filter-role').on('change', function () {
            table.ajax.reload();
        });

        // Filter button
        $('#btn-filter').on('click', function () {
            const keyword = $('#search_monitoring_user').val();
            table.search(keyword).ajax.reload();
            $('#monitoring-search-results').hide();
        });

        // Modal apply button
        $('#btn-modal-apply').on('click', function () {
            $('#filter-department').val($('#modal-filter-department').val());
            $('#filter-team').val($('#modal-filter-team').val());
            updateBadgeState();
            table.ajax.reload();
        });

        // Modal reset button
        $('#btn-modal-reset').on('click', function () {
            $('#modal-filter-department, #modal-filter-team').val('');
            $('#filter-department, #filter-team').val('');
            updateBadgeState();
            table.ajax.reload();
        });

        // Reset button
        $('#btn-reset').on('click', function () {
            $('#filter-division, #filter-role, #filter-department, #filter-team, #modal-filter-department, #modal-filter-team, #search_monitoring_user').val('');
            $('#filter-entries').val('15');
            $('#monitoring-search-results').hide();
            updateBadgeState();
            table.page.len(15).search('').ajax.reload();
        });

        // Search Input Keydown & Typing
        $('#search_monitoring_user').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#monitoring-search-results').hide();
                table.search(this.value).draw();
            }
        });

        // Live search autocomplete
        const $searchInput = $('#search_monitoring_user');
        const $results = $('#monitoring-search-results');
        const $list = $('#monitoring-autocomplete-list');
        const $footer = $('#monitoring-search-footer');

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }

        $searchInput.on('input', function () {
            clearTimeout(searchTimer);
            const query = this.value.trim();

            if (query.length < 1) {
                $results.hide();
                $list.empty();
                $footer.empty().hide();
                table.search('').draw();
                return;
            }

            searchTimer = setTimeout(() => {
                fetch(`{{ route('monitoring.live-search') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    $list.empty();
                    $footer.empty();

                    if (data && data.length > 0) {
                        data.forEach(item => {
                            const $item = $(`
                                <a href="javascript:void(0);" class="dropdown-item py-2 px-3 text-wrap d-flex align-items-center justify-content-between border-bottom-subtle" style="cursor: pointer;">
                                    <div class="d-flex flex-column min-w-0 me-2">
                                        <span class="text-truncate fw-medium text-dark">${escapeHtml(item.title)}</span>
                                        ${item.subtitle ? `<small class="text-muted text-truncate" style="font-size: 11px;">${escapeHtml(item.subtitle)}</small>` : ''}
                                    </div>
                                    <span class="badge bg-purple-subtle text-purple text-uppercase ms-2 flex-shrink-0" style="font-size: 10px;">${escapeHtml(item.type || 'USER')}</span>
                                </a>
                            `);

                            $item.on('click', function (e) {
                                e.preventDefault();
                                $searchInput.val(item.title);
                                $results.hide();
                                table.search(item.title).draw();
                            });

                            $list.append($item);
                        });

                        // "See all results" bottom footer link
                        const $seeAll = $(`
                            <a href="javascript:void(0);" class="dropdown-item fw-semibold text-primary py-1 d-flex align-items-center justify-content-center gap-2">
                                <i class="ti ti-search"></i>
                                <span>See all results for "<span class="search-keyword">${escapeHtml(query)}</span>"</span>
                            </a>
                        `);

                        $seeAll.on('click', function (e) {
                            e.preventDefault();
                            $results.hide();
                            table.search(query).draw();
                        });

                        $footer.append($seeAll).show();
                        $results.show();
                    } else {
                        $list.html('<div class="dropdown-item text-muted disabled py-2 px-3">No user found</div>');
                        $footer.hide();
                        $results.show();
                    }
                })
                .catch(() => {
                    $results.hide();
                });
            }, 300);
        });

        // Hide dropdown on click outside
        $(document).on('click', function (e) {
            if (!$searchInput.is(e.target) && !$results.is(e.target) && $results.has(e.target).length === 0) {
                $results.hide();
            }
        });

        // Reopen on focus if value exists
        $searchInput.on('focus', function () {
            if (this.value.trim().length >= 1 && $list.children().length > 0) {
                $results.show();
            }
        });

        @else
        $('#{{ $tableId }}-search').on('input', function () {
            clearTimeout(searchTimer);
            const keyword = this.value;
            searchTimer = setTimeout(() => table.search(keyword).draw(), 400);
        });
        @endif
    });
</script>
@endpush

