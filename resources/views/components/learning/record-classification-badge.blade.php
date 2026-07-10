@props(['row'])

@if ($row['row_kind'] === 'playlist')
    <span class="badge bg-secondary-lt text-secondary">Playlist</span>
@else
    @php
        $colors = [
            'SHE'                   => 'bg-red-lt text-red',
            'Technology'            => 'bg-blue-lt text-blue',
            'Basic & Common Duties' => 'bg-green-lt text-green',
            'Production'            => 'bg-indigo-lt text-indigo',
            'Commercial'            => 'bg-orange-lt text-orange',
            'Human Resources'       => 'bg-pink-lt text-pink',
            'Leadership'            => 'bg-purple-lt text-purple',
            'Quality'               => 'bg-cyan-lt text-cyan',
            'IT'                    => 'bg-azure-lt text-azure',
            'Security'              => 'bg-dark-lt text-dark',
        ];
    @endphp

    <span class="badge {{ $colors[$row['classification']] ?? 'bg-secondary-lt text-secondary' }}">{{ $row['classification'] }}</span>
@endif
