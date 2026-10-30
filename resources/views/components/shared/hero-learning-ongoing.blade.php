@props([
    'badge' => 'My Learning',
    'title' => 'Learning On Going',
    'subtitle' => null,
    'description' => 'Continue active learning and keep your development goals moving forward.',
    'mode' => 'content',
    'count' => 0,
    'stats' => [],
])

@php
    $mode = strtolower($mode ?? 'content');
    $modeLabel = match ($mode) {
        'playlist' => 'Playlist',
        'mooc'     => 'MOOC',
        'non-kp-owl', 'ongoing-non-kp-owl', 'offline' => 'Non KP OWL',
        default    => 'Content',
    };

    $heroCount = $count ?? (data_get($stats, '2.value', 0));
@endphp

<section {{ $attributes->class(['card mb-4 overflow-hidden learning-module-hero learning-module-hero--ongoing learning-hero-ds']) }}>
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
                <div class="learning-hero-ds-panel learning-hero-ds-panel--single" role="group"
                    aria-label="Ongoing learning statistics">
                    <div class="learning-hero-ds-card learning-hero-ds-card--primary learning-hero-ongoing-stat">
                        <span class="learning-hero-ongoing-title">TOTAL ON GOING</span>
                        <span class="learning-hero-ongoing-mode">{{ $modeLabel }}</span>
                        <strong class="learning-hero-ongoing-value">
                            {{ number_format((int) $heroCount) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
