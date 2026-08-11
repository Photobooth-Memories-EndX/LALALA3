@php
    $activeLabels = [];
    foreach ($filters['category'] as $value) $activeLabels[] = ['category', $value, $value];
    foreach ($filters['year'] as $value) $activeLabels[] = ['year', (string) $value, (string) $value];
    foreach ($filters['month'] as $value) $activeLabels[] = ['month', (string) $value, $monthOptions[$value] ?? (string) $value];
    if ($filters['date_from']) $activeLabels[] = ['date_from', $filters['date_from'], 'From ' . $filters['date_from']];
    if ($filters['date_to']) $activeLabels[] = ['date_to', $filters['date_to'], 'To ' . $filters['date_to']];
    if ($filters['likes']) $activeLabels[] = ['likes', $filters['likes'], $sortOptions[$filters['likes']] ?? $filters['likes']];
@endphp
@if($activeLabels)
    <div class="gallery-active-filters" aria-label="Active advanced filters">
        <span class="gallery-active-label">Active:</span>
        @foreach($activeLabels as [$parameter, $value, $label])
            <button type="button" class="gallery-filter-chip" data-gallery-clear-param="{{ $parameter }}" data-gallery-clear-value="{{ $value }}">
                <span>{{ $label }}</span><i class="ti ti-x" aria-hidden="true"></i><span class="visually-hidden">Remove {{ $label }} filter</span>
            </button>
        @endforeach
    </div>
@endif
