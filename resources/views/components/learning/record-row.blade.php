@props([
    // Satu baris Learning Record (array polos dari LearningRecordController).
    'row',
    // Nomor urut awal (dirender ulang oleh jQuery saat filter/pagination berubah).
    'index' => 1,
])

@php
    // Nilai untuk filter client-side (jQuery mencocokkan data-attribute, bukan HTML).
    $finishTimestamp = $row['finish_date'] !== '' ? \Illuminate\Support\Carbon::parse($row['finish_date']) : null;

    // Search: baris Content dicari pada title + classification, baris Playlist pada title saja
    // (perilaku sama seperti filter server-side sebelumnya).
    $searchText = $row['row_kind'] === 'content'
        ? $row['title'] . ' ' . $row['classification']
        : $row['title'];
@endphp

<tr class="record-row"
    data-kind="{{ $row['row_kind'] }}"
    data-search="{{ mb_strtolower($searchText) }}"
    data-type="{{ $row['type'] }}"
    data-classification="{{ $row['classification'] }}"
    data-playlists="{{ implode(',', $row['playlist_ids']) }}"
    data-year="{{ $finishTimestamp?->year }}"
    data-month="{{ $finishTimestamp?->month }}"
    data-cert="{{ $row['cert_state'] }}"
    {{-- Nilai sorting client-side (learning-record.js) — teks di-lowercase agar
         urutan tidak terpengaruh kapitalisasi. --}}
    data-sort-title="{{ mb_strtolower($row['title']) }}"
    data-sort-type="{{ mb_strtolower($row['type']) }}"
    data-sort-classification="{{ mb_strtolower($row['classification']) }}"
    data-sort-duration="{{ $row['duration'] }}"
    data-sort-progress="{{ $row['progress'] }}"
    data-sort-finish="{{ $row['finish_date'] }}"
    data-sort-score="{{ $row['score'] ?? '' }}">

    <td class="text-muted record-row-index">{{ $index }}</td>

    <td><x-learning.record-title-cell :row="$row" /></td>

    <td><x-learning.record-type-badge :type="$row['type']" :url="$row['playlist_url'] ?? null" /></td>

    <td><x-learning.record-classification-badge :row="$row" /></td>

    <td class="small">{{ $row['duration'] }}</td>

    <td><x-learning.record-progress-cell :row="$row" /></td>

    <td class="small">{{ $row['finish_date'] !== '' ? $row['finish_date'] : '-' }}</td>

    <td><x-learning.record-score-cell :row="$row" /></td>

    <td><x-learning.record-certificate-cell :row="$row" /></td>
</tr>
