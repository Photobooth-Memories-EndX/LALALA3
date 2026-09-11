@php
    $cards = [
        ['key' => 'total', 'label' => 'Total Certificate', 'icon' => 'ti-certificate', 'bg' => '#eef2ff', 'color' => '#003b6d'],
        ['key' => 'content', 'label' => 'Content Certificate', 'icon' => 'ti-book', 'bg' => '#e0f2fe', 'color' => '#0284c7'],
        ['key' => 'playlist', 'label' => 'Playlist Certificate', 'icon' => 'ti-playlist', 'bg' => '#f3e8ff', 'color' => '#7c3aed'],
        ['key' => 'global', 'label' => 'Global Certificate', 'icon' => 'ti-world', 'bg' => '#dcfce7', 'color' => '#16a34a'],
    ];
@endphp

<div class="row g-3 mb-3">
    @foreach($cards as $c)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border border-light-subtle shadow-sm rounded-3 h-100 summary-metric-card admin-card-bordered">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-box"
                         style="width: 44px; height: 44px; background-color: {{ $c['bg'] }}; color: {{ $c['color'] }};">
                        <i class="ti {{ $c['icon'] }} fs-2"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium stat-label">{{ $c['label'] }}</div>
                        <div class="h3 fw-bold mb-0 text-dark stat-value">{{ number_format($summary[$c['key']] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
