@props([
    // Satu baris Learning Record (array polos dari LearningRecordController).
    'row',
    // Nomor urut awal (dirender ulang oleh jQuery saat filter/pagination berubah).
    'index' => 1,
])

@php
    // Nilai untuk filter client-side (jQuery mencocokkan data-attribute, bukan HTML).
    $startDateRaw = (string) ($row['start_datetime'] ?? $row['start_date'] ?? '');
    $finishDateRaw = (string) ($row['end_datetime'] ?? $row['finish_date'] ?? '');
    $finishTimestamp = $finishDateRaw !== '' ? \Carbon\Carbon::parse($finishDateRaw) : null;
    $startDateDisplay = $startDateRaw !== ''
        ? \Carbon\Carbon::parse($startDateRaw)->format('d/m/y H:i:s')
        : '-';
    $finishDateDisplay = $finishTimestamp?->format('d/m/y H:i:s') ?? '-';

    // Search memakai title + classification untuk Content dan Playlist.
    $rowTitle = (string) ($row['title'] ?? '-');
    $rowClassification = (string) ($row['classification'] ?? '-');
    $rowCategory = (string) ($row['category'] ?? 'Content');
    $rowType = (string) ($row['type'] ?? 'Content');
    $rowKind = (string) ($row['row_kind'] ?? 'content');
    $searchText = $rowTitle . ' ' . $rowClassification;
    $rawReqLower = strtolower((string) ($row['requirement'] ?? $row['subtitle'] ?? '-'));
    $isNonMandRow = str_contains($rawReqLower, 'non-mandatory') || str_contains($rawReqLower, 'non mandatory') || str_contains($rawReqLower, 'optional');
    $learningProgram = $isNonMandRow ? 'optional'
        : (str_contains($rawReqLower, 'mandatory') ? 'mandatory' : 'optional');
    $progressValue = (int) ($row['progress'] ?? 0);
    $scoreVal = $row['score'] ?? $row['assessment_score'] ?? null;
    $hasScore = ($row['assessment_required'] ?? false) && !is_null($scoreVal);
    $scorePassed = $hasScore && ($scoreVal >= ($row['min_score'] ?? 0));
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
    $learningHourDisplay = str_replace([' hours', ' hour'], [' h', ' h'], $row['learning_hour'] ?? '-');
    $rawAtt = $row['attendance'] ?? null;
    $attLower = strtolower(trim((string) $rawAtt));
    $attendanceStatus = in_array($attLower, ['1', 'true', 'present', 'yes'], true)
        ? 'Yes'
        : (($rawAtt === null || $rawAtt === '') && $progressValue >= 100 ? 'Yes' : 'No');
@endphp

<tr class="record-row"
    data-kind="{{ $rowKind }}"
    data-category="{{ $rowCategory }}"
    data-search="{{ mb_strtolower($searchText) }}"
    data-type="{{ $rowType }}"
    data-classification="{{ $rowClassification }}"
    data-learning-program="{{ $learningProgram }}"
    data-learning-status="{{ $learningStatus }}"
    data-playlists="{{ implode(',', $row['playlist_ids'] ?? []) }}"
    data-learning-hour="{{ $row['learning_hour_value'] ?? '' }}"
    data-year="{{ $finishTimestamp?->year }}"
    data-month="{{ $finishTimestamp?->month }}"
    data-cert="{{ $row['cert_state'] ?? 'none' }}"
    {{-- Nilai sorting client-side (learning-record.js) — teks di-lowercase agar
         urutan tidak terpengaruh kapitalisasi. --}}
    data-sort-title="{{ mb_strtolower($rowTitle) }}"
    data-sort-category="{{ mb_strtolower($rowCategory) }}"
    data-sort-type="{{ mb_strtolower($rowType) }}"
    data-sort-classification="{{ mb_strtolower($rowClassification) }}"
    data-sort-duration="{{ $row['duration_sort'] ?? -1 }}"
    data-sort-learning-hour="{{ $row['learning_hour_bucket'] ?? 0 }}"
    data-sort-progress="{{ $progressValue }}"
    data-sort-finish="{{ $finishDateRaw }}"
    data-sort-attendance="{{ mb_strtolower($attendanceStatus) }}"
    data-sort-score="{{ $scoreVal ?? '' }}">

    <td class="text-muted record-row-index">{{ $index }}</td>

    <td><x-learning.record-title-cell :row="$row" /></td>

    <td class="learning-record-type text-center">
        <x-learning.record-type-badge :type="$row['type']" :url="(($row['can_open'] ?? true) ? ($row['playlist_url'] ?? null) : null)" />
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
                                        <span class="course-preview-playlist-number">{{ $loop->iteration }}.</span>
                                        @if (!empty($item['url']) && ($item['can_open'] ?? true))
                                            <a href="{{ $item['url'] }}" class="course-preview-playlist-link">
                                                {{ $item['title'] }}
                                            </a>
                                        @elseif (!empty($item['url']))
                                            <a href="javascript:void(0);"
                                               class="course-preview-playlist-link learning-record-blocked-trigger text-muted text-decoration-none"
                                               data-status="{{ $item['access_status'] ?? 'INACTIVE' }}"
                                               data-message="{{ $item['modal_message'] ?? 'This content is currently inactive.' }}"
                                               data-title="{{ $item['title'] ?? '-' }}">
                                                {{ $item['title'] }}
                                            </a>
                                        @else
                                            <span class="course-preview-playlist-link text-muted">{{ $item['title'] }}</span>
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
        <div class="text-xs text-muted">
            <div class="learning-record-date-row text-nowrap">Start : {{ $startDateDisplay }}</div>
            <div class="learning-record-date-row text-nowrap">Finish : {{ $finishDateDisplay }}</div>
        </div>
    </td>

    <td class="text-center learning-record-attendance">
        <x-learning.record-attendance-cell :row="$row" />
    </td>

    <td><x-learning.record-score-cell :row="$row" /></td>

    <td><x-learning.record-certificate-cell :row="$row" /></td>
</tr>
