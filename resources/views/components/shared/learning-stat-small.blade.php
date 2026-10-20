@props([
    'label',
    'value' => '—',
    'tone' => 'green',
])

@php
    $normalizedLabel = strtoupper(trim((string) $label));
    $statKey = match ($normalizedLabel) {
        'COURSE COMPLETED', 'CONTENT COMPLETED' => 'course',
        'PLAYLIST COMPLETED' => 'playlist',
        'INTERNAL TRAINING' => 'internal',
        'EXTERNAL TRAINING' => 'external',
        'OWL COURSE COMPLETED' => 'owl',
        'NON OWL COURSE COMPLETED' => 'non_owl',
        default => null,
    };
    $dsCardModifier = $statKey === 'course'
        ? 'learning-hero-ds-card--secondary'
        : 'learning-hero-ds-card--success';
@endphp

<div {{ $attributes->class([
    'learning-stat-card learning-stat-small learning-hero-ds-card',
    $dsCardModifier,
    'learning-stat--' . $tone,
]) }}>
    <div class="learning-stat-small-label learning-hero-ds-label">{{ $label }}</div>
    <div class="learning-stat-small-value learning-hero-ds-value" @if($statKey) data-record-stat="{{ $statKey }}" @endif>{{ $value }}</div>
</div>
