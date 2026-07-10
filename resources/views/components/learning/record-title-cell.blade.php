@props(['row'])

{{-- Kolom Class: hanya judul (link bila content masih ada) dan subtitle —
     badge Playlist tidak lagi ditampilkan di kolom ini (cukup di kolom Type). --}}

@if ($row['slug'])
    <a href="{{ route('content.user.view', $row['slug']) }}" class="fw-semibold text-reset">{{ $row['title'] }}</a>
@else
    <div class="fw-semibold">{{ $row['title'] }}</div>
@endif

@if (!empty($row['subtitle']))
    <div class="text-muted small mb-1">{{ $row['subtitle'] }}</div>
@endif
