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

    <td class="learning-record-category">
        @if (($row['category'] ?? '') === 'Playlist')
            <span class="badge bg-green-lt text-green learning-record-badge">Playlist</span>
        @else
            <span class="badge bg-blue-lt text-blue learning-record-badge">Content</span>
        @endif
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
