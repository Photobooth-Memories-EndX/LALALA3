@props([
    'badge' => 'Assessment',
    'title' => 'Assessment',
    'subtitle' => null,
    'description' => 'Track assessment activity and review the results you have achieved.',
    'stats' => [],
])

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
                <x-shared.learning-hero-summary
                    title="Assessment Summary"
                    :hero-stats="$stats"
                />
            </div>
        </div>
    </div>
</section>
