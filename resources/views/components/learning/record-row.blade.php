@props([
    // Satu baris Learning Record (array polos dari LearningRecordController).
    'row',
    // Nomor urut awal (dirender ulang oleh jQuery saat filter/pagination berubah).
    'index' => 1,
])

@php
    // Nilai untuk filter client-side (jQuery mencocokkan data-attribute, bukan HTML).
    $finishTimestamp = $row['finish_date'] !== '' ? \Illuminate\Support\Carbon::parse($row['finish_date']) : null;
    $startDateDisplay = ($row['start_date'] ?? '') !== ''
        ? \Illuminate\Support\Carbon::parse($row['start_date'])->format('d/m/y H:i:s')
        : '';
    $finishDateDisplay = $finishTimestamp?->format('d/m/y H:i:s') ?? '';

    // Search memakai title + classification untuk Content dan Playlist.
    $searchText = $row['title'] . ' ' . $row['classification'];
    $learningProgram = str_contains(strtolower($row['subtitle'] ?? ''), 'mandatory') ? 'mandatory'
        : (str_contains(strtolower($row['subtitle'] ?? ''), 'optional') ? 'optional' : '');
    $progressValue = (int) ($row['progress'] ?? 0);
    $hasScore = ($row['assessment_required'] ?? false) && !is_null($row['score'] ?? null);
    $scorePassed = $hasScore && ($row['score'] >= ($row['min_score'] ?? 0));
    $learningStatus = $progressValue >= 100
        ? ($scorePassed ? 'completed passed' : ($hasScore ? 'completed failed' : 'completed'))
        : ($hasScore ? ($scorePassed ? 'passed' : 'failed') : ($progressValue <= 0 ? 'not_started' : 'ongoing'));
    $durationSeconds = max(0, (int) ($row['duration_sort'] ?? 0));
    $durationDisplay = sprintf(
        '%02d:%02d:%02d',
        intdiv($durationSeconds, 3600),
        intdiv($durationSeconds % 3600, 60),
        $durationSeconds % 60
    );
    $learningHourDisplay = str_replace([' hours', ' hour'], [' h', ' h'], $row['learning_hour'] ?? '');
@endphp

<tr class="record-row"
    data-kind="{{ $row['row_kind'] }}"
    data-category="{{ $row['category'] }}"
    data-search="{{ mb_strtolower($searchText) }}"
    data-type="{{ $row['type'] }}"
    data-classification="{{ $row['classification'] }}"
    data-learning-program="{{ $learningProgram }}"
    data-learning-status="{{ $learningStatus }}"
    data-playlists="{{ implode(',', $row['playlist_ids']) }}"
    data-learning-hour="{{ $row['learning_hour_value'] ?? '' }}"
    data-year="{{ $finishTimestamp?->year }}"
    data-month="{{ $finishTimestamp?->month }}"
    data-cert="{{ $row['cert_state'] }}"
    {{-- Nilai sorting client-side (learning-record.js) — teks di-lowercase agar
         urutan tidak terpengaruh kapitalisasi. --}}
    data-sort-title="{{ mb_strtolower($row['title']) }}"
    data-sort-category="{{ mb_strtolower($row['category']) }}"
    data-sort-type="{{ mb_strtolower($row['type']) }}"
    data-sort-classification="{{ mb_strtolower($row['classification']) }}"
    data-sort-duration="{{ $row['duration_sort'] ?? -1 }}"
    data-sort-learning-hour="{{ $row['learning_hour_bucket'] ?? 0 }}"
    data-sort-progress="{{ $row['progress'] }}"
    data-sort-finish="{{ $row['finish_date'] }}"
    data-sort-score="{{ $row['score'] ?? '' }}">

    <td class="text-muted record-row-index">{{ $index }}</td>

    <td><x-learning.record-title-cell :row="$row" /></td>

    <td class="learning-record-type text-center">
        <x-learning.record-type-badge :type="$row['type']" :url="$row['playlist_url'] ?? null" />
    </td>

    <td class="learning-record-category text-center position-relative">
        @if (($row['category'] ?? '') === 'Playlist')
            <span class="badge bg-green-lt text-green learning-record-badge category-preview-trigger"
                  role="button"
                  tabindex="0"
                  data-preview-kind="playlist"
                  data-preview-title="{{ $row['title'] }}"
                  title="Click/hover to preview playlist contents">Playlist</span>
        @else
            <span class="badge bg-blue-lt text-blue learning-record-badge category-preview-trigger"
                  role="button"
                  tabindex="0"
                  data-preview-kind="content"
                  data-preview-title="{{ $row['title'] }}"
                  title="Click/hover to preview content info">Content</span>
        @endif

        {{-- Template Hover/Click Preview --}}
        <div class="d-none course-preview-template">
            @if ($row['row_kind'] === 'playlist')
                <div class="course-preview-popover course-preview-playlist">
                    <div class="course-preview-header">
                        <div class="course-preview-title">{{ $row['title'] }}</div>
                    </div>
                    <div class="course-preview-body">
                        @if (!empty($row['playlist_items']))
                            <ol class="course-preview-playlist-list mb-0">
                                @foreach ($row['playlist_items'] as $item)
                                    <li class="course-preview-playlist-item">
                                        @if (!empty($item['url']))
                                            <a href="{{ $item['url'] }}" class="course-preview-playlist-link">
                                                {{ $item['title'] }}
                                            </a>
                                        @else
                                            <span class="text-muted">{{ $item['title'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="text-muted small">No content in this playlist.</div>
                        @endif
                    </div>
                </div>
            @else
                <div class="course-preview-popover course-preview-content">
                    <div class="course-preview-header">
                        <div class="course-preview-title">{{ $row['title'] }}</div>
                    </div>
                    <div class="course-preview-body">
                        <div class="course-preview-info d-flex flex-column gap-1 pt-1">
                            @if (!empty($row['type']))
                                <div class="course-preview-item d-flex align-items-center gap-2">
                                    <span class="text-muted small">Type:</span>
                                    <x-learning.record-type-badge :type="$row['type']" />
                                </div>
                            @endif
                            @if (!empty($row['classification']) && $row['classification'] !== '-')
                                <div class="course-preview-item d-flex align-items-center gap-2">
                                    <span class="text-muted small">Classification:</span>
                                    <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                        {{ $row['classification'] }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </td>

    <td><x-learning.record-classification-badge :row="$row" /></td>

    <td class="learning-record-duration">{{ $durationDisplay }}</td>

    <td class="learning-record-learning-hour">{{ $learningHourDisplay }}</td>

    <td><x-learning.record-progress-cell :row="$row" /></td>

    <td class="text-muted small learning-record-datetime">
        <div class="learning-record-date-row text-nowrap"><strong>Start&nbsp;&nbsp;:</strong> {{ $startDateDisplay }}</div>
        <div class="learning-record-date-row text-nowrap"><strong>Finish :</strong> {{ $finishDateDisplay }}</div>
    </td>

    <td><x-learning.record-score-cell :row="$row" /></td>

    <td><x-learning.record-certificate-cell :row="$row" /></td>
</tr>
