@props([
    'badge' => 'My Learning',
    'title' => 'Learning On Going',
    'subtitle' => null,
    'description' => 'Continue active learning and keep your development goals moving forward.',
    'stats' => [],
])

<section {{ $attributes->class(['card border-0 shadow-sm mb-4 overflow-hidden learning-module-hero learning-module-hero--ongoing']) }}
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
                <div class="learning-hero-stats-panel learning-hero-stats-panel--ongoing">
                    <x-shared.learning-stat-large
                        class="learning-hero-stat-feature"
                        style="background: #6EA7D3; box-shadow: none; border: none;"
                        label="Total On Going"
                        :value="data_get($stats, '2.value', '—')"
                        description="Active learning in progress"
                        tone="blue"
                    />
                </div>
            </div>
        </div>
    </div>
</section>
