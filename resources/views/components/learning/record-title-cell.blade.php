@props(['row'])

{{-- Kolom Class: hanya judul (link bila content masih ada) dan subtitle —
     badge Playlist tidak lagi ditampilkan di kolom ini (cukup di kolom Type). --}}

@if ($row['slug'])
    <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link">{{ $row['title'] }}</a>
@elseif (!empty($row['playlist_url']))
    <a href="{{ $row['playlist_url'] }}" class="learning-record-link">{{ $row['title'] }}</a>
@else
    <div class="fw-semibold">{{ $row['title'] }}</div>
@endif

@if (!empty($row['subtitle']))
    <div class="text-muted small mb-1">{{ $row['subtitle'] }}</div>
@endif
