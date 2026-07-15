@props([
    'assessment',
    'isCompleted',
    'isPassed',
])

@if ($isCompleted && $isPassed)
    <button class="btn btn-sm btn-white" disabled>
        Completed
    </button>
@else
    @if (!empty($assessment->slug))
        <a href="{{ route('assesment.user.view', $assessment->slug) }}"
           class="btn btn-sm btn-primary">
            {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
        </a>
    @else
        <button class="btn btn-sm btn-secondary" disabled>
            Unavailable
        </button>
    @endif
@endif
