@props([
    'type',
    // URL halaman Playlist (route playlist.user.view) — hanya terisi pada baris Playlist.
    'url' => null,
])

@php
    $normalizedType = strtolower((string) $type);

    $class = match (true) {
        $normalizedType === 'playlist'                              => 'bg-green',
        in_array($normalizedType, ['video', 'youtube'], true)       => 'bg-yellow text-white',
        in_array($normalizedType, ['document', 'pdf', 'sop/regulation'], true)
                                                                    => 'bg-cyan',
        $normalizedType === 'content'                               => 'bg-red',
        in_array($normalizedType, ['quiz', 'assessment', 'assesment'], true)
                                                                    => 'bg-indigo-lt text-indigo',
        default                                                     => 'bg-orange-lt text-orange',
    };
@endphp

@if ($type === 'Playlist' && $url)
    <a href="{{ $url }}" class="badge {{ $class }} learning-record-badge record-playlist-link"><i class="ti ti-playlist me-1"></i>{{ $type }}</a>
@else
    <span class="badge {{ $class }} learning-record-badge">@if ($type === 'Playlist')<i class="ti ti-playlist me-1"></i>@endif{{ $type }}</span>
@endif
