@props([
    'latestScores' => collect(),
    'totalHistoryCount' => 0,
    'assessmentType' => null,
    'assessmentBatch' => null,
    'passingGrade' => null,
])

@php
    $isGlobal = strtolower((string) ($assessmentType ?? '')) === 'global';
@endphp

<div class="assessment-score-popover-card">
    <div class="assessment-score-popover-header d-flex justify-content-between align-items-center">
        <span>Assessment Results</span>
        @if ($totalHistoryCount > 0)
            <span class="badge bg-secondary-lt text-secondary font-weight-normal" style="font-size: 10px;">
                Total: {{ $totalHistoryCount }}
            </span>
        @endif
    </div>

    <div class="assessment-score-popover-body">
        @if ($latestScores->count() > 0)
            <table class="table table-sm table-borderless mb-0 assessment-mini-history-table">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-start">Date & Time</th>
                        <th class="text-center">Score</th>
                        @if ($isGlobal)
                            <th class="text-center">Batch</th>
                            <th class="text-center">Result</th>
                        @else
                            <th class="text-center">Attempt</th>
                            <th class="text-center">Result</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($latestScores as $scoreItem)
                        @php
                            $hasResult = $scoreItem->score !== null || $scoreItem->pass !== null;
                            $isPassed = (bool) ($scoreItem->pass ?? ($hasResult && $passingGrade !== null && $passingGrade !== '' ? ((float) $scoreItem->score >= (float) $passingGrade) : false));

                            $dateFormatted = '-';
                            if (!empty($scoreItem->created_at)) {
                                if (is_a($scoreItem->created_at, '\DateTimeInterface')) {
                                    $dateFormatted = $scoreItem->created_at->format('d/m/y H:i');
                                } else {
                                    $ts = strtotime((string) $scoreItem->created_at);
                                    $dateFormatted = $ts ? date('d/m/y H:i', $ts) : '-';
                                }
                            }

                            $scoreVal = $scoreItem->score !== null ? $scoreItem->score : '-';
                            $attemptNum = $scoreItem->attempt_number ?? $loop->iteration;
                            $totalAttempts = $scoreItem->total_attempts ?? $totalHistoryCount;
                            $attemptVal = $attemptNum . '/' . $totalAttempts;
                            $batchVal = $scoreItem->batch ?? ($assessmentBatch ?? '-');
                        @endphp
                        <tr>
                            <td class="text-center text-muted font-weight-medium fw-medium">{{ $attemptNum }}</td>
                            <td class="text-start text-muted text-nowrap">{{ $dateFormatted }}</td>
                            <td class="text-center font-weight-bold fw-semibold text-dark">{{ $scoreVal }}</td>
                            @if ($isGlobal)
                                <td class="text-center text-muted">{{ $batchVal }}</td>
                            @else
                                <td class="text-center text-muted">{{ $attemptVal }}</td>
                            @endif
                            <td class="text-center">
                                @if ($isPassed)
                                    <span class="badge bg-green-lt text-green px-2 py-1 assessment-status-passed">Passed</span>
                                @elseif ($hasResult)
                                    <span class="badge bg-red-lt text-red px-2 py-1 assessment-status-failed">Failed</span>
                                @else
                                    <span class="badge bg-orange-lt text-orange px-2 py-1 assessment-status-pending">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($totalHistoryCount > 5)
                <div class="assessment-score-popover-footer">
                    Showing first 5 attempts
                </div>
            @endif
        @else
            <div class="assessment-score-popover-empty">
                No assessment results available.
            </div>
        @endif
    </div>
</div>

