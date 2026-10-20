@props([
    'badge' => 'My Learning',
    'title' => 'Task',
    'subtitle' => null,
    'description' => 'Track tasks from the courses you joined and monitor their review status.',
    'stats' => [],
])

@php
    $taskStats = collect($stats)->mapWithKeys(function ($stat) {
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
                    aria-label="Task statistics">
                    <div class="learning-hero-ds-card learning-hero-ds-card--primary">
                        <span class="learning-hero-ds-label">Total Tasks</span>
                        <strong class="learning-hero-ds-value">
                            {{ $taskStats->get('total tasks', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--warning">
                        <span class="learning-hero-ds-label">Not Started</span>
                        <strong class="learning-hero-ds-value">
                            {{ $taskStats->get('not started', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--success">
                        <span class="learning-hero-ds-label">Completed</span>
                        <strong class="learning-hero-ds-value">
                            {{ $taskStats->get('completed', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--danger">
                        <span class="learning-hero-ds-label">Overdue</span>
                        <strong class="learning-hero-ds-value">
                            {{ $taskStats->get('overdue', '—') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
