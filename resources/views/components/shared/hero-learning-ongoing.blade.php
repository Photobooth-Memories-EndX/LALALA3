@props([
    'badge' => 'My Learning',
    'title' => 'Learning Dashboard',
    'subtitle' => null,
    'description' => 'Continue active learning and keep your development goals moving forward.',
    'stats' => [],
])

<section {{ $attributes->class(['card border-0 shadow-sm mb-4 overflow-hidden learning-module-hero learning-module-hero--ongoing']) }}>
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
                <x-shared.learning-hero-summary
                    title="Learning Summary"
                    :hero-stats="$stats"
                />
            </div>
        </div>
    </div>
</section>
