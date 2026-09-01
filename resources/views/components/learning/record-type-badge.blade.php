@props([
    'type',
    // URL halaman Playlist (route playlist.user.view) — hanya terisi pada baris Playlist.
    'url' => null,
])

@php
    $normalizedType = strtolower((string) $type);

    $class = match (true) {
        $normalizedType === 'playlist'                              => 'bg-green text-white',
        in_array($normalizedType, ['video', 'youtube'], true)       => 'bg-yellow text-white',
        in_array($normalizedType, ['document', 'pdf', 'sop/regulation'], true)
                                                                    => 'bg-cyan text-white',
        $normalizedType === 'content'                               => 'bg-purple-lt text-purple',
        in_array($normalizedType, ['quiz', 'assessment', 'assesment'], true)
                                                                    => 'bg-indigo-lt text-indigo',
        $normalizedType === 'she'                                   => 'bg-red-lt text-red',
        default                                                     => 'bg-orange-lt text-orange',
    };
    
    $isPlaylist = in_array($normalizedType, ['playlist'], true);
@endphp

@if ($isPlaylist && $url)
    <a href="{{ $url }}" class="badge {{ $class }} learning-record-type-badge record-playlist-link"><i class="ti ti-playlist me-1" aria-hidden="true"></i>{{ \App\Support\ContentTypeHelper::label($type) }}</a>
@else
    <span class="badge {{ $class }} learning-record-type-badge">@if ($isPlaylist)<i class="ti ti-playlist me-1" aria-hidden="true"></i>@endif{{ \App\Support\ContentTypeHelper::label($type) }}</span>
@endif
