@props([
    'badge' => 'My Learning',
    'title' => 'Learning Record',
    'subtitle' => null,
    'description' => 'Review your learning history, completion progress, and learning hours.',
    'recordMode' => null,
    'stats' => [],
])

@php
    $rawMode = strtolower(trim((string) ($recordMode ?? data_get($stats, 'record_mode') ?? request('record_mode', 'online'))));
    $recordMode = match ($rawMode) {
        'offline', 'non_owl', 'non-owl' => 'offline',
        'all'                           => 'all',
        default                         => 'online',
    };

    $rawTotalHours = data_get($stats, 'total_hours', data_get($stats, 'totalLearningHours', data_get($stats, '2.value', 0)));
    if (is_string($rawTotalHours)) {
        $rawTotalHours = (float) str_replace(',', '', $rawTotalHours);
    }

    $rawOnlineHours = data_get($stats, 'online_hours', data_get($stats, 'kp_owl_hours', 0));
    if (is_string($rawOnlineHours)) {
        $rawOnlineHours = (float) str_replace(',', '', $rawOnlineHours);
    }

    $rawOfflineHours = data_get($stats, 'offline_hours', data_get($stats, 'non_kp_owl_hours', 0));
    if (is_string($rawOfflineHours)) {
        $rawOfflineHours = (float) str_replace(',', '', $rawOfflineHours);
    }

    $stats = [
        'total_hours'        => (float) ($rawTotalHours ?? 0),
        'online_hours'       => (float) ($rawOnlineHours ?? 0),
        'offline_hours'      => (float) ($rawOfflineHours ?? 0),
        'kp_owl_hours'       => (float) ($rawOnlineHours ?? 0),
        'non_kp_owl_hours'   => (float) ($rawOfflineHours ?? 0),
        'internal_count'     => (int) str_replace(',', '', (string) data_get($stats, 'internal_count', 0)),
        'external_count'     => (int) str_replace(',', '', (string) data_get($stats, 'external_count', 0)),
        'owl_completed'      => (int) str_replace(',', '', (string) data_get($stats, 'owl_completed', data_get($stats, 'content_completed', data_get($stats, 'courseCompleted', data_get($stats, '0.value', 0))))),
        'non_owl_completed'  => (int) str_replace(',', '', (string) data_get($stats, 'non_owl_completed', 0)),
        'content_completed'  => (int) str_replace(',', '', (string) data_get($stats, 'content_completed', data_get($stats, 'courseCompleted', data_get($stats, '0.value', 0)))),
        'playlist_completed' => (int) str_replace(',', '', (string) data_get($stats, 'playlist_completed', data_get($stats, 'playlistCompleted', data_get($stats, '1.value', 0)))),
    ];
@endphp

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
                    @if($recordMode === 'offline')
                        {{-- Non-KP OWL Header Cards --}}
                        <x-shared.learning-stat-large label="LEARNING HOUR (NON KP OWL)" :value="number_format($stats['total_hours'], 2)" />
                        <div class="d-flex flex-column gap-2">
                            <x-shared.learning-stat-small label="INTERNAL TRAINING" :value="$stats['internal_count']" />
                            <x-shared.learning-stat-small label="EXTERNAL TRAINING" :value="$stats['external_count']" />
                        </div>
                    @elseif($recordMode === 'all')
                        {{-- All Records Header Cards --}}
                        <x-shared.learning-stat-large label="TOTAL LEARNING HOUR (ALL RECORD)" :value="number_format($stats['total_hours'], 2)" />
                        <div class="d-flex flex-column gap-2">
                            <x-shared.learning-stat-small label="LEARNING HOUR OWL" :value="number_format($stats['online_hours'], 2)" />
                            <x-shared.learning-stat-small label="LEARNING HOUR NON KP OWL" :value="number_format($stats['offline_hours'], 2)" />
                        </div>
                    @else
                        {{-- Default KP OWL Header Cards --}}
                        <x-shared.learning-stat-large label="LEARNING HOUR (KP OWL)" :value="number_format($stats['total_hours'], 2)" />
                        <div class="d-flex flex-column gap-2">
                            <x-shared.learning-stat-small label="COURSE COMPLETED" :value="$stats['content_completed']" />
                            <x-shared.learning-stat-small label="PLAYLIST COMPLETED" :value="$stats['playlist_completed']" />
    
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
