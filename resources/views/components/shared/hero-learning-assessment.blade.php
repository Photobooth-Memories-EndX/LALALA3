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

<section {{ $attributes->class(['card border-0 shadow-sm mb-4 overflow-hidden learning-module-hero learning-module-hero--assessment']) }}>
    <div class="card-body p-4 p-lg-5 text-white">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-3">
                    {{ $badge }}
                </span>

                <h2 class="text-white mb-2">{{ $title }}</h2>

                @if (filled($subtitle))
                    <p class="mb-2">{{ $subtitle }}</p>
                @endif

                <p class="mb-0" style="opacity:.9">{{ $description }}</p>
            </div>

            <div class="col-lg-4 d-none d-lg-block text-end">
                <div class="learning-hero-stats-panel learning-hero-stats-panel--assessment">
                    <div class="learning-hero-stats-assessment">
                        <x-shared.learning-stat-small
                            label="Total Assessment"
                            :value="$assessmentStats->get('total assessment', '—')"
                            tone="blue"
                        />
                        <x-shared.learning-stat-small
                            label="Started"
                            :value="$assessmentStats->get('started', '—')"
                            tone="cyan"
                        />
                        <x-shared.learning-stat-small
                            label="Passed"
                            :value="$assessmentStats->get('passed', $assessmentStats->get('assessment passed', '—'))"
                            tone="green"
                        />
                        <x-shared.learning-stat-small
                            label="Failed"
                            :value="$assessmentStats->get('failed', '—')"
                            tone="red"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
