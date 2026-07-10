@props(['type'])

@php
    $class = match (true) {
        $type === 'Playlist'                                        => 'bg-purple-lt text-purple',
        in_array($type, ['Video', 'YouTube'], true)                 => 'bg-blue-lt text-blue',
        in_array($type, ['Quiz', 'Assessment', 'Assesment'], true)  => 'bg-indigo-lt text-indigo',
        default                                                     => 'bg-orange-lt text-orange', // Document, dll.
    };
@endphp

<span class="badge {{ $class }} learning-record-badge">@if ($type === 'Playlist')<i class="ti ti-playlist me-1"></i>@endif{{ $type }}</span>
