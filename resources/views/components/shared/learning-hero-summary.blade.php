@props([
    'title' => 'Learning Summary',
    'subtitle' => 'Your learning at a glance',
    'heroStats' => [],
])

@php
    $summaryStats = collect(is_iterable($heroStats) ? $heroStats : [])->values();
@endphp

<div class="learning-module-hero-summary d-inline-block w-75 mw-100 p-2 text-start border border-white border-opacity-25 rounded-3">
    <div class="d-flex align-items-center gap-2 mb-1">
        <span class="learning-module-hero-summary-icon d-inline-flex flex-shrink-0 align-items-center justify-content-center rounded-2">
            <i class="ti ti-chart-donut-3" aria-hidden="true"></i>
        </span>

        <div class="overflow-hidden">
            <div class="learning-module-hero-summary-title text-truncate">
                {{ $title }}
            </div>

            @if (filled($subtitle))
                <div class="learning-module-hero-summary-subtitle text-truncate">
                    {{ $subtitle }}
                </div>
            @endif
        </div>
    </div>

    <div class="row g-1">
        @forelse ($summaryStats as $stat)
            <div class="{{ $summaryStats->count() % 2 === 1 && $loop->last ? 'col-12' : 'col-6' }} d-flex">
                <div class="learning-module-hero-stat-tile d-flex h-100 w-100 flex-column justify-content-center rounded-2">
                    <div class="learning-module-hero-stat-label text-truncate"
                         title="{{ $stat['label'] ?? 'Statistic' }}">
                        {{ $stat['label'] ?? 'Statistic' }}
                    </div>
                    <div class="learning-module-hero-stat-value text-truncate">
                        {{ $stat['value'] ?? 0 }}
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 d-flex">
                <div class="learning-module-hero-stat-tile d-flex h-100 w-100 flex-column justify-content-center rounded-2">
                    <div class="learning-module-hero-stat-label">No summary data available.</div>
                    <div class="learning-module-hero-stat-value">&mdash;</div>
                </div>
            </div>
        @endforelse
    </div>
</div>
