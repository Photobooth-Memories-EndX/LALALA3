@props([
    'url',
    'isCompleted',
    'isPassed',
    'userAttempts' => 0,
    'totalRemedials' => 0,
])

<div class="d-flex flex-column align-items-center justify-content-center">
    @if ($isCompleted && $isPassed)
        <button class="btn btn-sm assessment-action-btn btn-assessment-completed" disabled>
            <i class="ti ti-check"></i>
            <span>Completed</span>
        </button>
    @else
        @if ($url)
            <a href="{{ $url }}"
               class="btn btn-sm assessment-action-btn {{ $isCompleted ? 'btn-assessment-remedial' : 'btn-assessment-start' }}">
                <i class="ti {{ $isCompleted ? 'ti-refresh' : 'ti-clipboard-check' }}"></i>
                <span>{{ $isCompleted ? 'Remedial' : 'Take Test' }}</span>
            </a>
        @else
            <button class="btn btn-sm assessment-action-btn btn-assessment-unavailable" disabled>
                <i class="ti ti-lock"></i>
                <span>Unavailable</span>
            </button>
        @endif
    @endif

    @if ($totalRemedials > 0)
        <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
            {{ $userAttempts }}/{{ $totalRemedials }} Attempts
        </span>
    @else
        <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
            -
        </span>
    @endif
</div>
