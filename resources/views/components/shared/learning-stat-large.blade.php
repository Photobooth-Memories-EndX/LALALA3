@props([
    'label',
    'value' => '—',
    'description' => null,
    'tone' => 'blue',
])

@php
    $normalizedLabel = strtoupper(trim((string) $label));
    $statKey = $normalizedLabel === 'TOTAL LEARNING HOURS' ? 'hours' : null;
@endphp

<div {{ $attributes->class([
    'learning-stat-card learning-stat-large learning-hero-ds-card learning-hero-ds-card--primary',
    'learning-stat--' . $tone,
]) }}>
    <div class="learning-stat-large-label learning-hero-ds-label">
        @if (str_contains(strtoupper(strip_tags((string) $label)), 'TOTAL LEARNING HOUR') && str_contains(strtoupper(strip_tags((string) $label)), 'ALL RECORD'))
            <span class="fs-7 fw-bold text-uppercase tracking-wider text-white opacity-90 d-block text-center">
                TOTAL LEARNING HOUR<br>(ALL RECORD)
            </span>
        @else
            {!! $label !!}
        @endif
    </div>
    <div class="learning-stat-large-value learning-hero-ds-value learning-hero-ds-value--large" @if($statKey) data-record-stat="{{ $statKey }}" @endif>{{ $value }}</div>

    @if (filled($description))
        <div class="learning-stat-large-description">{{ $description }}</div>
    @endif
</div>
