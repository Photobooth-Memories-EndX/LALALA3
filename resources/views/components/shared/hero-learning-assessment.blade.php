@props([
    'badge' => 'My Learning',
    'title' => 'Assessment',
    'subtitle' => null,
    'description' => 'Track assessment activity and review the results you have achieved.',
    'stats' => [],
])

@php
    $assessmentStats = collect($stats)->mapWithKeys(function ($stat) {
        $label = strtolower(trim((string) data_get($stat, 'label', '')));

        return $label === ''
            ? []
            : [$label => data_get($stat, 'value', '—')];
    });
@endphp

<section {{ $attributes->class(['card mb-4 overflow-hidden learning-module-hero learning-module-hero--assessment learning-hero-ds']) }}>
    <div class="card-body p-4 p-lg-5 text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-3 learning-hero-ds-badge">
                    {{ $badge }}
                </span>

                <h2 class="text-white mb-2 learning-hero-ds-title">{{ $title }}</h2>

                @if (filled($subtitle))
                    <p class="mb-2 learning-hero-ds-subtitle">{{ $subtitle }}</p>
                @endif

                <p class="mb-0 learning-hero-ds-description">{{ $description }}</p>
            </div>

            <div class="col-lg-4 d-flex justify-content-lg-end">
                <div class="learning-hero-ds-panel learning-hero-ds-panel--grid" role="group"
                    aria-label="Assessment statistics">
                    <div class="learning-hero-ds-card learning-hero-ds-card--primary">
                        <span class="learning-hero-ds-label">Total Assessment</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('total assessment', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--warning">
                        <span class="learning-hero-ds-label">Pending</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('started', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--success">
                        <span class="learning-hero-ds-label">Passed</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('passed', $assessmentStats->get('assessment passed', '—')) }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--danger">
                        <span class="learning-hero-ds-label">Failed</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('failed', '—') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
