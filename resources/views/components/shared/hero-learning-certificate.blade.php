@props([
    'badge' => 'Certificate',
    'title' => 'Certificate',
    'subtitle' => null,
    'description' => 'View your achievements and access certificates when they become available.',
    'stats' => [],
])

@php
    $heroStats = collect(is_iterable($stats) ? $stats : [])->values();
@endphp

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
                <div class="learning-module-hero-summary d-inline-block w-75 mw-100 p-2 text-start border border-white border-opacity-25 rounded-3">
                    <div class="fw-semibold mb-2">
                        Certificate Summary
                    </div>

                    <div class="row g-2">
                        @forelse ($heroStats as $stat)
                            <div class="{{ $heroStats->count() % 2 === 1 && $loop->last ? 'col-12' : 'col-6' }}">
                                <div class="small opacity-75">{{ $stat['label'] ?? 'Statistic' }}</div>
                                <div class="h2 text-white mb-0">{{ $stat['value'] ?? 0 }}</div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="small opacity-75">No certificate data available.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
