@props(['row'])

{{-- Kolom Class: judul (link bila content masih ada), status, dan badge playlist pemilik. --}}

@if ($row['slug'])
    <a href="{{ route('content.user.view', $row['slug']) }}" class="fw-semibold text-reset">{{ $row['title'] }}</a>
@else
    <div class="fw-semibold">{{ $row['title'] }}</div>
@endif

@if (!empty($row['subtitle']))
    <div class="text-muted small mb-1">{{ $row['subtitle'] }}</div>
@endif

@if (!empty($row['playlist_names']))
    @php
        $tooltip = '';
        foreach ($row['playlist_names'] as $playlist) {
            $tooltip .= "• {$playlist}\n";
        }
    @endphp

    <span class="badge bg-purple-lt text-purple mt-1"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          data-bs-html="true"
          title="{!! nl2br(e($tooltip)) !!}">
        <i class="ti ti-playlist me-1"></i>
        Playlist
    </span>
@endif
