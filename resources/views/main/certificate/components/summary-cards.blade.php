@php
    $cards = [
        ['total', 'Total Settings', 'ti-settings', 'blue'],
        ['content', 'Content Settings', 'ti-book', 'azure'],
        ['playlist', 'Playlist Settings', 'ti-route', 'purple'],
        ['global', 'Global Settings', 'ti-world', 'green'],
        ['active', 'Active Settings', 'ti-circle-check', 'green'],
        ['inactive', 'Inactive Settings', 'ti-circle-x', 'red'],
        ['required', 'Required Assessment', 'ti-clipboard-check', 'teal'],
        ['optional', 'Optional Assessment', 'ti-clipboard', 'yellow'],
    ];
@endphp

<div class="row row-cards mb-3">
    @foreach($cards as [$key, $label, $icon, $color])
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card certificate-summary-card">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar bg-{{ $color }}-lt text-{{ $color }} mr-3"><i class="ti {{ $icon }}"></i></span>
                    <div>
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="h2 mb-0">{{ number_format($summary[$key]) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
