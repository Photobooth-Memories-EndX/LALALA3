@php
    $playlistFilterData = collect($trainingplan->items())->map(function ($playlist) use ($playlistStats) {
        $progress = (int) ($playlistStats[$playlist->id]['progress'] ?? 0);
        $classificationIds = $playlist->trainingplandetail
            ->map(fn ($detail) => optional($detail->content)->classification_id)
            ->filter()
            ->map(fn ($classificationId) => (string) $classificationId)
            ->unique()
            ->values();

        return [
            'status' => $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started'),
            'month' => (int) $playlist->created_at->format('n'),
            'year' => (int) $playlist->created_at->format('Y'),
            'hasDeadline' => (bool) $playlist->has_deadline,
            'materials' => (int) ($playlistStats[$playlist->id]['totalContent'] ?? 0),
            'classifications' => $classificationIds,
            'duration' => (int) $playlist->total_duration_seconds,
        ];
    })->values();
@endphp

<script>
    $(function () {
        const $items = $('.playlist-item');
        const playlistFilterData = @json($playlistFilterData);

        $items.each(function (index) {
            $(this).data('playlistFilters', playlistFilterData[index] || {});
        });

        function updateFilterState() {
            const activeCount = [
                $('#playlistClassificationFilter').val() !== 'all',
                $('#playlistDurationFilter').val() !== 'all',
                $('#playlistDeadlineFilter').val() !== 'all',
                $('#playlistMaterialsFilter').val() !== 'all',
                $('#playlistSort').val() !== 'az'
            ].filter(Boolean).length;
            const hasActiveFilters = activeCount > 0 ||
                $('#playlistStatusFilter').val() !== 'all' ||
                $('#playlistMonthFilter').val() !== 'all' ||
                $('#playlistYearFilter').val() !== 'all' ||
                Boolean(($('#playlistSearch').val() || '').trim());

            $('#playlistMoreFiltersCount')
                .text(activeCount || '')
                .toggleClass('d-none', activeCount === 0);
            $('#playlistResetFilter').toggleClass('is-active', hasActiveFilters);
        }

        function filterPlaylist() {
            const keyword = ($('#playlistSearch').val() || '').trim().toLowerCase();
            const status = $('#playlistStatusFilter').val();
            const month = $('#playlistMonthFilter').val();
            const year = $('#playlistYearFilter').val();
            const classification = $('#playlistClassificationFilter').val();
            const duration = $('#playlistDurationFilter').val();
            const deadline = $('#playlistDeadlineFilter').val();
            const materials = $('#playlistMaterialsFilter').val();
            const sort = $('#playlistSort').val();

            let visibleItems = $items.toArray();

            visibleItems.forEach(function (item) {
                const $item = $(item);
                const itemFilters = $item.data('playlistFilters') || {};
                const title = $item.find('.playlist-title').text().toLowerCase();
                const content = $item.find('.playlist-content-list').text().toLowerCase();

                const matchesKeyword = !keyword || title.includes(keyword) || content.includes(keyword);
                const matchesStatus = status === 'all' || itemFilters.status === status;
                const matchesMonth = month === 'all' || String(itemFilters.month) === month;
                const matchesYear = year === 'all' || String(itemFilters.year) === year;
                const matchesClassification = classification === 'all' ||
                    (itemFilters.classifications || []).includes(classification);
                const durationSeconds = Number(itemFilters.duration || 0);
                const matchesDuration = duration === 'all' ||
                    (duration === '0_30' && durationSeconds <= 1800) ||
                    (duration === '30_60' && durationSeconds > 1800 && durationSeconds <= 3600) ||
                    (duration === '60_120' && durationSeconds > 3600 && durationSeconds <= 7200) ||
                    (duration === 'over_120' && durationSeconds > 7200);
                const matchesDeadline = deadline === 'all' ||
                    (deadline === 'has_deadline' && itemFilters.hasDeadline) ||
                    (deadline === 'no_deadline' && !itemFilters.hasDeadline);
                const materialCount = Number(itemFilters.materials || 0);
                const matchesMaterials = materials === 'all' ||
                    (materials === '1_5' && materialCount >= 1 && materialCount <= 5) ||
                    (materials === '6_10' && materialCount >= 6 && materialCount <= 10) ||
                    (materials === '11_20' && materialCount >= 11 && materialCount <= 20) ||
                    (materials === 'over_20' && materialCount > 20);

                $item.toggle(
                    matchesKeyword && matchesStatus && matchesMonth && matchesYear &&
                    matchesClassification && matchesDuration && matchesDeadline && matchesMaterials
                );
            });

            visibleItems = $items.filter(':visible').toArray();

            visibleItems.sort(function (a, b) {
                const titleA = $(a).find('.playlist-title').text().trim().toLowerCase();
                const titleB = $(b).find('.playlist-title').text().trim().toLowerCase();

                if (sort === 'az') {
                    return titleA.localeCompare(titleB);
                }

                if (sort === 'za') {
                    return titleB.localeCompare(titleA);
                }

                return 0;
            });

            $('#playlistGrid').append(visibleItems);
            updateFilterState();
        }

        $('#playlistSearch').on('input', function () {
            filterPlaylist();
        });

        $('#playlistSearchAction, #playlistApplyFilter, #playlistApplyMoreFilters').on('click', function () {
            filterPlaylist();
        });

        $('#playlistSearch').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterPlaylist();
            }
        });

        $('#playlistStatusFilter, #playlistMonthFilter, #playlistYearFilter').on('change', function () {
            updateFilterState();
        });

        $('#playlistClassificationFilter, #playlistDurationFilter, #playlistDeadlineFilter, #playlistMaterialsFilter, #playlistSort').on('change', function () {
            updateFilterState();
        });

        $('#playlistResetFilter').on('click', function () {
            $('#playlistSearch').val('');
            $('#playlistStatusFilter, #playlistMonthFilter, #playlistYearFilter').val('all');
            $('#playlistClassificationFilter, #playlistDurationFilter').val('all');
            $('#playlistDeadlineFilter, #playlistMaterialsFilter').val('all');
            $('#playlistSort').val('az');
            filterPlaylist();
        });

        updateFilterState();
    });
</script>
