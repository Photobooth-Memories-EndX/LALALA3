<div class="row g-3 align-items-start mb-3">
    <div class="col">
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

                            <span class="badge bg-purple-lt text-purple">
                                Batch: {{ $assesment->batch ?? '-' }}
                            </span>

                            @if ($assesment->hasAccessPeriod() && $assesment->fromdate && $assesment->todate)
                                <span class="badge bg-orange-lt text-orange">
                                    Period: {{ date('d M H:i', strtotime($assesment->fromdate)) }} – {{ date('d M H:i', strtotime($assesment->todate)) }}
                                </span>
                            @endif

                            @if ($assesment->hasTimeLimit())
                                <span class="badge bg-blue-lt text-blue">
                                    Time Limit: {{ $assesment->time_limit_minutes }} mins
                                </span>
                            @elseif (!$assesment->hasAccessPeriod())
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

    @if (!empty($countDownDate) || !empty($expiresAtIso))
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 1rem; z-index: 10;">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">Sisa Waktu</div>
                    <div class="h2 mb-0 text-danger fw-bold" id="timer">Loading...</div>
                </div>
            </div>
        </div>
    @endif
</div>
