@props([
    'category',
])

@php
    $cat = trim((string) ($category ?? '—'));
    $catLower = strtolower($cat);

    // Normalize inadvertent 'content' to 'All Content' to strictly enforce no 'Content' badge
    if ($catLower === 'content') {
        $cat = 'All Content';
        $catLower = 'all content';
    }

    $class = match ($catLower) {
        'playlist'    => 'badge bg-teal-lt text-teal text-nowrap',
        'all content' => 'badge bg-purple-lt text-purple text-nowrap',
        'global'      => 'badge bg-indigo-lt text-indigo text-nowrap',
        default       => 'text-muted',
    };
@endphp

@if (in_array($catLower, ['playlist', 'all content', 'global'], true))
    <span class="{{ $class }} learning-record-badge assessment-category-badge">{{ $cat }}</span>
@else
    <span class="text-muted">—</span>
@endif
