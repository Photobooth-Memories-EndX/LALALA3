@props([
    'badge' => 'My Learning',
    'title' => 'Learning Record',
    'subtitle' => null,
    'description' => 'Review your learning history, completion progress, and learning hours.',
    'stats' => [],
])

<section {{ $attributes->class(['card mb-4 overflow-hidden learning-module-hero learning-module-hero--record learning-hero-ds']) }}>
    <div class="card-body p-4 p-lg-5 text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 learning-hero-record-content">
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
                <div class="learning-hero-ds-panel learning-hero-ds-panel--split" role="group"
                    aria-label="Learning record statistics">
                    <div class="learning-hero-ds-card learning-hero-ds-card--primary">
                        <span class="learning-hero-ds-label">Total Learning Hours</span>
                        <strong class="learning-hero-ds-value learning-hero-ds-value--large">
                            {{ data_get($stats, '2.value', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-stack">
                        <div class="learning-hero-ds-card learning-hero-ds-card--secondary">
                            <span class="learning-hero-ds-label">Course Completed</span>
                            <strong class="learning-hero-ds-value">
                                {{ data_get($stats, '0.value', '—') }}
                                {{-- 1002 --}}
                            </strong>
                        </div>

                        <div class="learning-hero-ds-card learning-hero-ds-card--success">
                            <span class="learning-hero-ds-label">Playlist Completed</span>
                            <strong class="learning-hero-ds-value">
                                {{ data_get($stats, '1.value', '—') }}
                                {{-- 1945 --}}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
