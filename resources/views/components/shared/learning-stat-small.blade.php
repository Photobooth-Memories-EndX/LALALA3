@props([
    'label',
    'value' => '—',
    'tone' => 'blue',
])

<div {{ $attributes->class([
    'learning-stat-card learning-stat-small',
    'learning-stat--' . $tone,
]) }}>
    <div class="learning-stat-small-label">{{ $label }}</div>
    <div class="learning-stat-small-value">{{ $value }}</div>
</div>
