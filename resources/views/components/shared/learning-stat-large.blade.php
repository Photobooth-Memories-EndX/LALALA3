@props([
    'label',
    'value' => '—',
    'description' => null,
    'tone' => 'blue',
])

<div {{ $attributes->class([
    'learning-stat-card learning-stat-large',
    'learning-stat--' . $tone,
]) }}>
    <div class="learning-stat-large-label">{{ $label }}</div>
    <div class="learning-stat-large-value">{{ $value }}</div>

    @if (filled($description))
        <div class="learning-stat-large-description">{{ $description }}</div>
    @endif
</div>
