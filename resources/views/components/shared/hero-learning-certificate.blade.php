@props([
    'badge' => 'My Learning',
    'title' => 'Certificate',
    'subtitle' => null,
    'description' => 'View your achievements and access certificates when they become available.',
    'stats' => [],
])

<section {{ $attributes->class(['card border-0 shadow-sm mb-4 overflow-hidden learning-module-hero learning-module-hero--certificate']) }}
    style="--hero-background: linear-gradient(90deg, #05507D 0%, #0A5D91 55%, #3F97CF 100%);">
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
                <div class="learning-stat-widget" role="group" aria-label="Certificate statistics">
                    <div class="learning-stat-widget-card learning-stat-widget-feature learning-stat-widget-card--dark-blue">
                        <span class="learning-stat-widget-label">Total Certificate</span>
                        <strong class="learning-stat-widget-value learning-stat-widget-value--large">
                            {{ data_get($stats, '0.value', '—') }}
                        </strong>
                    </div>

                    <div class="learning-stat-widget-stack">
                        <div class="learning-stat-widget-card learning-stat-widget-card--blue">
                            <span class="learning-stat-widget-label">Playlist Certificate</span>
                            <strong class="learning-stat-widget-value">
                                {{ data_get($stats, '1.value', '—') }}
                            </strong>
                        </div>

                        <div class="learning-stat-widget-card learning-stat-widget-card--green">
                            <span class="learning-stat-widget-label">Content Certificate</span>
                            <strong class="learning-stat-widget-value">
                                {{ data_get($stats, '2.value', '—') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
