@props(['row'])

@if ($row['row_kind'] === 'playlist')
    <span class="badge bg-secondary-lt text-secondary learning-record-badge learning-classification-badge">Playlist</span>
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

        $classification = $row['classification'] ?? '';
        $badgeColor = $colors[$classification] ?? null;

        if (! $badgeColor) {
            foreach ($colors as $key => $color) {
                if (strcasecmp($key, $classification) === 0) {
                    $badgeColor = $color;
                    break;
                }
            }
        }

        $badgeColor = $badgeColor ?? 'bg-secondary-lt text-secondary';
    @endphp

    <span class="badge {{ $badgeColor }} learning-record-badge learning-classification-badge">{{ $classification }}</span>
@endif

