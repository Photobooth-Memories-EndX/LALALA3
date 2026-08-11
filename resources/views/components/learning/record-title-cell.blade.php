@props(['row'])

{{-- Kolom Class: hanya judul (link bila content masih ada) dan subtitle —
     badge Playlist tidak lagi ditampilkan di kolom ini (cukup di kolom Type). --}}

<div class="learning-record-title">
    @if ($row['slug'])
        <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link">{{ $row['title'] }}</a>
    @elseif (!empty($row['playlist_url']))
        <a href="{{ $row['playlist_url'] }}" class="learning-record-link">{{ $row['title'] }}</a>
    @else
        <span class="learning-record-title-text">{{ $row['title'] }}</span>
    @endif
</div>

@if (!empty($row['subtitle']))
    <div class="learning-record-subtitle text-muted">{{ $row['subtitle'] }}</div>
@endif
