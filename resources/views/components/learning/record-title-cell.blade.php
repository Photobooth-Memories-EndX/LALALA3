@props(['row'])

<div class="learning-record-title mb-1">
    @if ($row['slug'])
        <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link fw-semibold">{{ $row['title'] }}</a>
    @elseif (!empty($row['playlist_url']))
        <a href="{{ $row['playlist_url'] }}" class="learning-record-link fw-semibold">{{ $row['title'] }}</a>
    @else
        <span class="learning-record-title-text fw-semibold">{{ $row['title'] }}</span>
    @endif
</div>

<div class="d-flex align-items-center mt-1">
    {{-- Type Badge: Video / Document / Playlist (Compact) --}}
    <x-learning.record-type-badge :type="$row['type']" :url="$row['playlist_url'] ?? null" />
</div>
