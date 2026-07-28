@props([
    'badge' => 'My Learning',
    'title' => 'Assessment',
    'subtitle' => null,
    'description' => 'Track assessment activity and review the results you have achieved.',
    'stats' => [],
])

@php
    $assessmentStats = collect($stats)->mapWithKeys(function ($stat) {
        $label = strtolower(trim((string) data_get($stat, 'label', '')));

        return $label === ''
            ? []
            : [$label => data_get($stat, 'value', '—')];
    });
@endphp

<style>
    .learning-hero-ds {
        --learning-hero-border: 1px solid rgba(255, 255, 255, .18);
        --learning-hero-radius: 14px;
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

    .learning-hero-ds-panel {
        display: grid;
        box-sizing: border-box;
        width: 100%;
        max-width: 360px;
        height: 160px;
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
        padding: 6px 8px;
        overflow: hidden;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
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
        background: #05507D;
    }

    .learning-hero-ds-card--secondary {
        background: #6EA7D3;
    }

    .learning-hero-ds-card--success {
        background: #2EAD72;
    }

    .learning-hero-ds-card--warning {
        background: #FDBA3B;
        color: #17324D;
    }

    .learning-hero-ds-card--danger {
        background: #E11923;
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
</style>

<section {{ $attributes->class(['card mb-4 overflow-hidden learning-module-hero learning-module-hero--assessment learning-hero-ds']) }}>
    <div class="card-body p-4 p-lg-5 text-white">
        <div class="row align-items-center">
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

            <div class="col-lg-4 d-none d-lg-block text-end">
                <div class="learning-hero-ds-panel learning-hero-ds-panel--grid" role="group"
                    aria-label="Assessment statistics">
                    <div class="learning-hero-ds-card learning-hero-ds-card--primary">
                        <span class="learning-hero-ds-label">Total Assessment</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('total assessment', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--warning">
                        <span class="learning-hero-ds-label">Pending</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('started', '—') }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--success">
                        <span class="learning-hero-ds-label">Passed</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('passed', $assessmentStats->get('assessment passed', '—')) }}
                        </strong>
                    </div>

                    <div class="learning-hero-ds-card learning-hero-ds-card--danger">
                        <span class="learning-hero-ds-label">Failed</span>
                        <strong class="learning-hero-ds-value">
                            {{ $assessmentStats->get('failed', '—') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
