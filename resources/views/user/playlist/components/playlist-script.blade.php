@php
    $today = \Carbon\Carbon::now()->startOfDay();
    $playlistFilterData = collect($trainingplan->items())->map(function ($playlist) use ($playlistStats, $today) {
        $progress = (int) ($playlistStats[$playlist->id]['progress'] ?? 0);
        $details = $playlist->trainingplandetail->filter(fn ($d) => !empty($d->content));

        $classificationIds = $details
            ->map(fn ($detail) => optional($detail->content)->classification_id)
            ->filter()
            ->map(fn ($classificationId) => (string) $classificationId)
            ->unique()
            ->values();

        $authors = $details
            ->map(fn ($detail) => optional($detail->content)->author)
            ->filter(fn ($author) => !empty(trim($author ?? '')))
            ->unique()
            ->values();

        $totalViews = $details->sum(fn ($detail) => (int) (optional($detail->content)->views ?? 0));

        $toDate = $playlist->to_date ? \Carbon\Carbon::parse($playlist->to_date)->startOfDay() : null;
        $fromDate = $playlist->from_date ? \Carbon\Carbon::parse($playlist->from_date)->startOfDay() : null;
        $createdAt = $playlist->created_at ? $playlist->created_at->format('Y-m-d') : '';

        $deadlineStatus = 'all';
        if ($toDate) {
            if ($today->gt($toDate)) {
                $deadlineStatus = 'expired';
            } elseif ($today->eq($toDate)) {
                $deadlineStatus = 'today';
            } else {
                $daysDiff = (int) $today->diffInDays($toDate, false);
                if ($daysDiff <= 7) {
                    $deadlineStatus = 'less_than_7_days';
                } elseif ($daysDiff <= 30) {
                    $deadlineStatus = 'less_than_30_days';
                } else {
                    $deadlineStatus = 'more_than_30_days';
                }
            }
        }

        return [
            'id' => $playlist->id,
            'status' => $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started'),
            'month' => (int) $playlist->created_at->format('n'),
            'year' => (int) $playlist->created_at->format('Y'),
            'hasDeadline' => (bool) $playlist->has_deadline,
            'materials' => (int) ($playlistStats[$playlist->id]['totalContent'] ?? 0),
            'classifications' => $classificationIds,
            'duration' => (int) $playlist->total_duration_seconds,
            'learningHours' => $playlist->learning_hours !== null ? (float) $playlist->learning_hours : null,
            'authors' => $authors,
            'views' => $totalViews,
            'createdAt' => $createdAt,
            'fromDate' => $fromDate ? $fromDate->format('Y-m-d') : '',
            'toDate' => $toDate ? $toDate->format('Y-m-d') : '',
            'deadlineStatus' => $deadlineStatus,
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
            const authorVal = $('#playlistFilterAuthor').val() || '';
            const sortVal = $('#playlistFilterSort').val() || '';
            const viewOrderVal = $('input[name="playlist_view_order"]:checked').val() || '';
            const durationVal = $('input[name="playlist_duration"]:checked').val() || '';
            const learningHourVal = $('input[name="playlist_learning_hour"]:checked').val() || '';
            const classificationVal = $('#playlistClassificationFilter').length ? ($('#playlistClassificationFilter').val() || '') : '';

            const activeModalCount = [
                Boolean(authorVal) && authorVal !== 'all',
                Boolean($('#playlistFilterDeadlineFromDate').val()),
                Boolean($('#playlistFilterDeadlineToDate').val()),
                Boolean($('#playlistFilterCreatedFromDate').val()),
                Boolean($('#playlistFilterCreatedToDate').val()),
                Boolean(sortVal) && !['all', 'default', 'latest'].includes(sortVal),
                Boolean(viewOrderVal) && !['', 'default', 'all'].includes(viewOrderVal),
                Boolean(durationVal) && !['all', 'default'].includes(durationVal),
                $('input[name="playlist_total_pages[]"]:checked').length > 0,
                Boolean(learningHourVal) && !['all', 'default'].includes(learningHourVal),
                Boolean(classificationVal) && classificationVal !== 'all'
            ].filter(Boolean).length;

            const hasActiveFilters = activeModalCount > 0 ||
                (($('#playlistStatusFilter').val() || '') !== '' && $('#playlistStatusFilter').val() !== 'all') ||
                (($('#playlistMonthFilter').val() || '') !== '' && $('#playlistMonthFilter').val() !== 'all') ||
                (($('#playlistYearFilter').val() || '') !== '' && $('#playlistYearFilter').val() !== 'all') ||
                Boolean(($('#playlist-search-input, #playlistSearch').val() || '').trim());

            if (activeModalCount > 0) {
                $('#playlistMoreFiltersCount').text(activeModalCount).removeClass('d-none');
            } else {
                $('#playlistMoreFiltersCount').text('').addClass('d-none');
            }

            $('#playlistResetFilter').toggleClass('is-active', hasActiveFilters);
            $('#playlistResetMoreFilters').toggleClass('is-active', activeModalCount > 0);
        }

        function filterPlaylist() {
            const keyword = ($('#playlist-search-input, #playlistSearch').val() || '').trim().toLowerCase();
            const status = $('#playlistStatusFilter').val();
            const month = $('#playlistMonthFilter').val();
            const year = $('#playlistYearFilter').val();

            // Modal filters
            const author = $('#playlistFilterAuthor').val();
            const deadlineFromDate = $('#playlistFilterDeadlineFromDate').val();
            const deadlineToDate = $('#playlistFilterDeadlineToDate').val();
            const createdFromDate = $('#playlistFilterCreatedFromDate').val();
            const createdToDate = $('#playlistFilterCreatedToDate').val();
            const sort = $('#playlistFilterSort').val();
            const viewOrder = $('input[name="playlist_view_order"]:checked').val();
            const duration = $('input[name="playlist_duration"]:checked').val();
            const learningHour = $('input[name="playlist_learning_hour"]:checked').val() || 'all';
            const selectedTotalPages = $('input[name="playlist_total_pages[]"]:checked').map(function () {
                return $(this).val();
            }).get();
            const classification = $('#playlistClassificationFilter').val();

            let visibleItems = $items.toArray();

            visibleItems.forEach(function (item) {
                const $item = $(item);
                const itemFilters = $item.data('playlistFilters') || {};
                const title = $item.find('.playlist-title').text().toLowerCase();
                const content = $item.find('.playlist-content-list').text().toLowerCase();

                const matchesKeyword = !keyword || title.includes(keyword) || content.includes(keyword);
                const matchesStatus = !status || status === 'all' || itemFilters.status === status;
                const matchesMonth = !month || month === 'all' || parseInt(itemFilters.month, 10) === parseInt(month, 10);
                const matchesYear = !year || year === 'all' || parseInt(itemFilters.year, 10) === parseInt(year, 10);

                // Author
                const matchesAuthor = !author || (itemFilters.authors || []).includes(author);

                // Deadline date range
                const matchesDeadlineFrom = !deadlineFromDate || (itemFilters.toDate && itemFilters.toDate >= deadlineFromDate);
                const matchesDeadlineTo = !deadlineToDate || (itemFilters.toDate && itemFilters.toDate <= deadlineToDate);

                // Created date range
                const matchesCreatedFrom = !createdFromDate || (itemFilters.createdAt && itemFilters.createdAt >= createdFromDate);
                const matchesCreatedTo = !createdToDate || (itemFilters.createdAt && itemFilters.createdAt <= createdToDate);

                // Duration (in minutes)
                const durationSeconds = Number(itemFilters.duration || 0);
                const matchesDuration = duration === 'all' ||
                    (duration === 'lt10' && durationSeconds < 600) ||
                    (duration === '10-30' && durationSeconds >= 600 && durationSeconds <= 1800) ||
                    (duration === '30-60' && durationSeconds > 1800 && durationSeconds <= 3600) ||
                    (duration === 'gt60' && durationSeconds > 3600);

                // Learning Hour (in hours)
                const playlistLh = itemFilters.learningHours !== null && itemFilters.learningHours !== undefined
                    ? Number(itemFilters.learningHours)
                    : (durationSeconds / 3600);
                const matchesLearningHour = learningHour === 'all' ||
                    (learningHour === 'lt1h' && playlistLh < 1) ||
                    (learningHour === '1-3h' && playlistLh >= 1 && playlistLh <= 3) ||
                    (learningHour === 'gt3h' && playlistLh > 3);

                // Total Pages / Materials
                const materialCount = Number(itemFilters.materials || 0);
                let matchesTotalPages = true;
                if (selectedTotalPages.length > 0) {
                    matchesTotalPages = selectedTotalPages.some(function (val) {
                        if (val === 'lt10') return materialCount < 10;
                        if (val === '10-20') return materialCount >= 10 && materialCount <= 20;
                        if (val === '21-50') return materialCount >= 21 && materialCount <= 50;
                        if (val === 'gt50') return materialCount > 50;
                        return false;
                    });
                }

                // Classification
                const matchesClassification = !classification || classification === 'all' ||
                    (itemFilters.classifications || []).includes(classification);

                $item.toggle(
                    matchesKeyword && matchesStatus && matchesMonth && matchesYear &&
                    matchesAuthor && matchesDeadlineFrom && matchesDeadlineTo &&
                    matchesCreatedFrom && matchesCreatedTo &&
                    matchesDuration && matchesLearningHour && matchesTotalPages && matchesClassification
                );
            });

            visibleItems = $items.filter(':visible').toArray();

            // Sort items
            visibleItems.sort(function (a, b) {
                const dataA = $(a).data('playlistFilters') || {};
                const dataB = $(b).data('playlistFilters') || {};

                // View Order takes precedence if set
                if (viewOrder === 'most_viewed') {
                    return Number(dataB.views || 0) - Number(dataA.views || 0);
                }
                if (viewOrder === 'least_viewed') {
                    return Number(dataA.views || 0) - Number(dataB.views || 0);
                }

                // Order sort dropdown
                if (sort === 'oldest' || sort === 'oldest_course' || sort === 'asc' || sort === 'ascending') {
                    return (dataA.createdAt || '').localeCompare(dataB.createdAt || '');
                }
                if (sort === 'newest' || sort === 'newest_course' || sort === 'desc' || sort === 'descending') {
                    return (dataB.createdAt || '').localeCompare(dataA.createdAt || '');
                }

                return 0;
            });

            $('#playlistGrid').append(visibleItems);

            if (visibleItems.length === 0) {
                $('#playlistFilterEmpty').removeClass('d-none');
                $('.playlist-pagination').addClass('d-none');
            } else {
                $('#playlistFilterEmpty').addClass('d-none');
                $('.playlist-pagination').removeClass('d-none');
            }

            updateFilterState();
        }

        function escapePlaylistHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Live Autocomplete Search for Playlist Filter ────────────────────────
        function initPlaylistAutocomplete() {
            var searchInput = document.getElementById('playlist-search-input') || document.getElementById('playlistSearch');
            var searchResults = document.getElementById('playlist-search-results');
            var debounceTimer = null;

            if (!searchInput || !searchResults) return;

            $(searchInput).on('input', function () {
                var value = $.trim(this.value);
                clearTimeout(debounceTimer);

                // Filter local items immediately
                filterPlaylist();

                if (value.length < 1) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch('/playlist/search-autocomplete?q=' + encodeURIComponent(value), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || data.length === 0) {
                            searchResults.innerHTML = '<div class="dropdown-item text-muted small py-2 px-3">No playlists found</div>';
                        } else {
                            var html = '';
                            data.forEach(function (item) {
                                var countLabel = (item.total_contents || 0) + ' ' + (item.total_contents === 1 ? 'Content' : 'Contents');
                                var hoursLabel = item.learning_hours ? item.learning_hours + ' hrs' : '';

                                var subDetails = [];
                                subDetails.push(countLabel);
                                if (hoursLabel) subDetails.push(hoursLabel);

                                html += '<a href="#" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle playlist-search-item" data-title="' + escapePlaylistHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapePlaylistHtml(item.title) + '</span>' +
                                        '<span class="badge bg-teal-lt text-teal small flex-shrink-0">PLAYLIST</span>' +
                                    '</div>' +
                                    '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.join(' &bull; ') + '</div>' +
                                '</a>';
                            });
                            searchResults.innerHTML = html;
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
                }, 250);
            });

            $(searchInput).on('focus', function () {
                if ($.trim(this.value).length >= 1 && searchResults.children.length > 0) {
                    searchResults.style.display = 'block';
                }
            });

            $(document).on('click', '.playlist-search-item', function (event) {
                event.preventDefault();
                var title = String($(this).data('title') || '');
                $('#playlist-search-input, #playlistSearch').val(title);
                if (searchResults) searchResults.style.display = 'none';
                filterPlaylist();
            });

            document.addEventListener('click', function (e) {
                if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }

        initPlaylistAutocomplete();

        // Event listeners
        $('#playlistSearchAction, #playlistApplyFilter, #playlistApplyMoreFilters').on('click', function () {
            filterPlaylist();
        });

        $('#playlist-search-input, #playlistSearch').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var searchResults = document.getElementById('playlist-search-results');
                if (searchResults) searchResults.style.display = 'none';
                filterPlaylist();
            }
        });

        $('#playlistStatusFilter, #playlistMonthFilter, #playlistYearFilter').on('change', function () {
            updateFilterState();
            filterPlaylist();
        });

        $('#playlistFilterAuthor, #playlistFilterDeadlineFromDate, #playlistFilterDeadlineToDate, #playlistFilterCreatedFromDate, #playlistFilterCreatedToDate, #playlistFilterSort, #playlistClassificationFilter').on('change', function () {
            updateFilterState();
        });

        $('input[name="playlist_view_order"], input[name="playlist_duration"], input[name="playlist_total_pages[]"], input[name="playlist_learning_hour"]').on('change', function () {
            updateFilterState();
        });

        function resetAllFilters() {
            $('#playlist-search-input, #playlistSearch').val('');
            var searchResults = document.getElementById('playlist-search-results');
            if (searchResults) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
            }
            $('#playlistStatusFilter, #playlistMonthFilter, #playlistYearFilter').val('');
            $('#playlistFilterAuthor, #playlistFilterDeadlineFromDate, #playlistFilterDeadlineToDate, #playlistFilterCreatedFromDate, #playlistFilterCreatedToDate').val('').attr('type', 'text');
            $('#playlistFilterSort').val('all');
            if ($('#playlistClassificationFilter').length) {
                $('#playlistClassificationFilter').val('all');
            }
            $('input[name="playlist_view_order"][value=""]').prop('checked', true);
            $('input[name="playlist_duration"][value="all"]').prop('checked', true);
            $('input[name="playlist_learning_hour"][value="all"]').prop('checked', true);
            $('input[name="playlist_total_pages[]"]').prop('checked', false);
            filterPlaylist();
        }

        $('#playlistResetFilter, #playlistResetMoreFilters').on('click', function () {
            resetAllFilters();
        });

        updateFilterState();
    });
</script>
