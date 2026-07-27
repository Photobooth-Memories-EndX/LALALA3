@props([
    'badge' => 'Certificate',
    'title' => 'Certificate',
    'subtitle' => null,
    'description' => 'View your achievements and access certificates when they become available.',
    'stats' => [],
])

<section {{ $attributes->class(['card border-0 shadow-sm mb-4 overflow-hidden learning-module-hero learning-module-hero--certificate']) }}>
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
                <div class="learning-hero-stats-panel">
                    <div class="learning-hero-stats-paired">
                        <x-shared.learning-stat-small
                            label="Certificates"
                            :value="data_get($stats, '0.value', '—')"
                            tone="green"
                        />
                        <x-shared.learning-stat-small
                            label="Passed Assessment"
                            :value="data_get($stats, '1.value', '—')"
                            tone="orange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
