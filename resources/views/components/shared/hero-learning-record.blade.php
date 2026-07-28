@props([
    'badge' => 'My Learning',
    'title' => 'Learning Record',
    'subtitle' => null,
    'description' => 'Review your learning history, completion progress, and learning hours.',
    'stats' => [],
])

<style>
    .learning-hero-ds {
        --learning-hero-border: 1px solid rgba(255, 255, 255, .18);
        --learning-hero-radius: 22px;
        background: #05507D !important;
        border: var(--learning-hero-border) !important;
        border-radius: var(--learning-hero-radius) !important;
        box-shadow: none !important;
        filter: none !important;
    }

    .learning-hero-ds::before,
    .learning-hero-ds::after {
        display: none !important;
    }

    .learning-hero-ds-badge {
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: .04em;
    }

    .learning-hero-ds-title {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }

    .learning-hero-ds-subtitle,
    .learning-hero-ds-description {
        font-size: 15px;
        font-weight: 400;
        line-height: 1.45;
    }

    .learning-hero-ds-description {
        opacity: .9;
    }

    .learning-hero-ds > .card-body {
        display: flex;
        box-sizing: border-box;
        min-height: 170px;
        padding: 15px 34px !important;
        align-items: center;
    }

    .learning-hero-ds > .card-body > .row {
        width: 100%;
        min-height: 140px;
    }

    .learning-hero-ds-panel {
        display: grid;
        box-sizing: border-box;
        width: 100%;
        max-width: 360px;
        height: 140px;
        margin-left: auto;
        gap: 8px;
        padding: 8px;
        overflow: hidden;
        border: var(--learning-hero-border);
        border-radius: var(--learning-hero-radius);
        background: transparent;
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    .learning-hero-ds-panel--split {
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    }

    .learning-hero-ds-panel--grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-ds-panel--single {
        grid-template-columns: minmax(0, 1fr);
    }

    .learning-hero-ds-stack {
        display: grid;
        min-width: 0;
        min-height: 0;
        gap: 8px;
        grid-template-rows: repeat(2, minmax(0, 1fr));
    }

    .learning-hero-ds-card {
        display: flex;
        container-type: inline-size;
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        min-height: 0;
        padding: 4px 8px;
        overflow: hidden;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        border: var(--learning-hero-border);
        border-radius: var(--learning-hero-radius);
        color: #fff;
        text-align: center;
        box-shadow: none;
        filter: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        transition: none;
    }

    .learning-hero-ds-card:hover {
        box-shadow: none;
        transform: none;
    }

    .learning-hero-ds-card--primary {
        background: #2563EB;
    }

    .learning-hero-ds-card--secondary {
        background: #22C55E;
    }

    .learning-hero-ds-card--success {
        background: #22C55E;
    }

    .learning-hero-ds-card--warning {
        background: #FDBA3B;
        color: #17324D;
    }

    .learning-hero-ds-card--danger {
        background: #EF4444;
    }

    .learning-hero-ds-label {
        display: flex;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: inherit;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: .04em;
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
    }

    .learning-hero-ds-value {
        display: block;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        color: inherit;
        font-size: 32px;
        font-size: clamp(24px, 18cqw, 32px);
        font-weight: 800;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
    }

    .learning-hero-ds-value--large {
        font-size: 42px;
        font-size: clamp(32px, 22cqw, 42px);
    }

    @media (max-width: 767.98px) {
        .learning-hero-ds > .card-body,
        .learning-hero-ds > .card-body > .row {
            min-height: auto;
        }

        .learning-hero-ds-panel {
            margin-left: 0;
        }
    }
</style>

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
                        <span class="learning-hero-ds-label">Learning Hours</span>
                        <strong class="learning-hero-ds-value learning-hero-ds-value--large">
                            {{ data_get($stats, '2.value', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-stack">
                        <div class="learning-hero-ds-card learning-hero-ds-card--secondary">
                            <span class="learning-hero-ds-label">Course Completed</span>
                            <strong class="learning-hero-ds-value">
                                {{-- {{ data_get($stats, '0.value', '—') }} --}}
                                1002
                            </strong>
                        </div>

                        <div class="learning-hero-ds-card learning-hero-ds-card--success">
                            <span class="learning-hero-ds-label">Playlist Completed</span>
                            <strong class="learning-hero-ds-value">
                                {{-- {{ data_get($stats, '1.value', '—') }} --}}
                                1945
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
