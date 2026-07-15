@props([
    'url',
    'isCompleted',
    'isPassed',
])

@if ($isCompleted && $isPassed)
    <button class="btn btn-sm btn-white assessment-action-btn" disabled>
        Completed
    </button>
@else
    @if ($url)
        <a href="{{ $url }}"
           class="btn btn-sm btn-primary assessment-action-btn">
            {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
        </a>
    @else
        <button class="btn btn-sm btn-secondary assessment-action-btn" disabled>
            Unavailable
        </button>
    @endif
@endif
