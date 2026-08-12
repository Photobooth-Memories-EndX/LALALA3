@props([
    'url' => null,
    'isCompleted' => false,
    'isPassed' => false,
    'isExpired' => false,
    'userAttempts' => 0,
    'maxAttempts' => 0,
    'totalRemedials' => 0,
    'assessmentId' => null,
    'assessmentTitle' => null,
    'assessmentBatch' => null,
    'passingGrade' => null,
    'fromDate' => null,
    'toDate' => null,
])

@php
    $maxAttemptsDisplay = $maxAttempts > 0 ? $maxAttempts : 1 + (int) $totalRemedials;
@endphp

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
        @if ($userAttempts >= $maxAttemptsDisplay)
            <button type="button"
                    class="btn btn-sm assessment-action-btn btn-assessment-failed"
                    disabled>
                <i class="ti ti-x"></i>
                <span>Failed</span>
            </button>
        @elseif ($url)
            <a href="{{ $url }}"
               class="btn btn-sm assessment-action-btn btn-assessment-remedial btn-remedial"
               data-assessment-id="{{ $assessmentId }}"
               data-assessment-title="{{ $assessmentTitle }}"
               data-assessment-batch="{{ $assessmentBatch }}"
               data-assessment-passing-grade="{{ $passingGrade ?? '-' }}"
               data-assessment-from-date="{{ $fromDate ?? '-' }}"
               data-assessment-to-date="{{ $toDate ?? '-' }}"
               data-assessment-user-attempts="{{ $userAttempts }}"
               data-assessment-max-attempts="{{ $maxAttemptsDisplay }}"
               data-assessment-total-remedials="{{ $totalRemedials }}"
               data-assessment-action-type="remedial">
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
           class="btn btn-sm assessment-action-btn btn-assessment-start btn-start-test"
           data-assessment-id="{{ $assessmentId }}"
           data-assessment-title="{{ $assessmentTitle }}"
           data-assessment-batch="{{ $assessmentBatch }}"
           data-assessment-passing-grade="{{ $passingGrade ?? '-' }}"
           data-assessment-from-date="{{ $fromDate ?? '-' }}"
           data-assessment-to-date="{{ $toDate ?? '-' }}"
           data-assessment-user-attempts="{{ $userAttempts }}"
           data-assessment-max-attempts="{{ $maxAttemptsDisplay }}"
           data-assessment-total-remedials="{{ $totalRemedials }}"
           data-assessment-action-type="start">
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
        @if ($maxAttemptsDisplay > 0)
            <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
                {{ $userAttempts }}/{{ $maxAttemptsDisplay }} Attempts
            </span>
        @else
            <span class="badge badge-outline-secondary rounded-pill px-2 py-1 mt-2 assessment-remedial-badge">
                -
            </span>
        @endif
    @endif
</div>
