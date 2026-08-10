@props([
    'url' => null,
    'isCompleted' => false,
    'isPassed' => false,
    'isExpired' => false,
    'userAttempts' => 0,
    'totalRemedials' => 0,
])

<div class="d-flex flex-column align-items-center justify-content-center">
    @if ($isPassed)
        <button type="button" class="btn btn-sm assessment-action-btn btn-assessment-passed" disabled>
            <i class="ti ti-check"></i>
            <span>Passed</span>
        </button>
    @elseif ($isExpired)
        <button type="button" class="btn btn-sm assessment-action-btn btn-assessment-expired" disabled>
            <i class="ti ti-clock-off"></i>
            <span>Expired</span>
        </button>
    @elseif ($isCompleted)
        @if ($url)
            <a href="{{ $url }}"
               class="btn btn-sm assessment-action-btn btn-assessment-remedial">
                <i class="ti ti-refresh"></i>
                <span>Remedial</span>
            </a>
        @else
            <button type="button" class="btn btn-sm assessment-action-btn btn-assessment-unavailable" disabled>
                <i class="ti ti-lock"></i>
                <span>Unavailable</span>
            </button>
        @endif
    @elseif ($url)
        <a href="{{ $url }}"
           class="btn btn-sm assessment-action-btn btn-assessment-start">
            <i class="ti ti-player-play"></i>
            <span>Start Test</span>
        </a>
    @else
        <button type="button" class="btn btn-sm assessment-action-btn btn-assessment-unavailable" disabled>
            <i class="ti ti-lock"></i>
            <span>Unavailable</span>
        </button>
    @endif

    @if (!$isPassed && !$isExpired)
        @if ($totalRemedials > 0)
            <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
                {{ $userAttempts }}/{{ $totalRemedials }} Attempts
            </span>
        @else
            <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
                -
            </span>
        @endif
    @endif
</div>
