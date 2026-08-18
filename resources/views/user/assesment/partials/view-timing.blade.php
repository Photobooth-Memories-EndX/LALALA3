@php
    $hasPeriod = $assesment->hasAccessPeriod() && !empty($assesment->fromdate) && !empty($assesment->todate);
    $hasTimer = $assesment->hasTimeLimit();
    $hasDeadline = $hasPeriod || $hasTimer;

    $assessmentTypeLabel = match (strtolower((string) ($assesment->type ?? ''))) {
        'content' => 'Content Assessment',
        'trainingplan', 'playlist' => 'Playlist Assessment',
        'global' => 'Global Assessment',
        default => !empty($assesment->type) ? ucwords(str_replace('_', ' ', $assesment->type)) . ' Assessment' : 'Assessment',
    };

    $isGlobal = ($assesment->type ?? '') === 'global';
    $calcCurrentAttempt = $currentAttempt ?? 1;
    $calcMaxAttempts = $maxAttempts ?? (1 + (int) ($assesment->total_remedials ?? 0));
@endphp

<div class="row g-3 align-items-start mb-3">
    <div class="{{ $hasDeadline ? 'col-lg-9' : 'col-12' }}">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-fill">
                        <div class="text-muted small mb-1">Assessment</div>
                        <h2 class="mb-1">{{ $assesment->title ?? '-' }}</h2>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-blue-lt text-blue">
                                Passing Grade: {{ $assesment->passing_grade ?? '-' }}
                            </span>

                            <span class="badge bg-cyan-lt text-cyan">
                                Type: {{ $assessmentTypeLabel }}
                            </span>

                            @if ($isGlobal)
                                <span class="badge bg-purple-lt text-purple">
                                    Batch: {{ $assesment->batch ?? '-' }}
                                </span>
                            @else
                                <span class="badge bg-indigo-lt text-indigo">
                                    Attempt: {{ $calcCurrentAttempt }} / {{ $calcMaxAttempts }}
                                </span>
                            @endif

                            @if ($hasPeriod)
                                <span class="badge bg-orange-lt text-orange">
                                    Assessment Period: {{ date('d M H:i', strtotime($assesment->fromdate)) }} - {{ date('d M H:i', strtotime($assesment->todate)) }}
                                </span>
                            @endif

                            @if ($hasTimer)
                                <span class="badge bg-blue-lt text-blue">
                                    Time Limit: {{ $assesment->time_limit_minutes }} mins
                                </span>
                            @endif

                            @if (!$hasDeadline)
                                <span class="badge bg-green-lt text-green">
                                    No Deadline
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($hasDeadline && (!empty($countDownDate) || !empty($expiresAtIso)))
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 1rem; z-index: 10;">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">Time Remaining</div>
                    <div id="timer" class="text-danger fw-bold">
                        <div class="small text-uppercase text-muted fw-semibold mb-1" id="timer-days">-- DAYS</div>
                        <div class="h2 mb-0 font-monospace text-danger fw-bold" id="timer-clock">--:--:--</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
