@props([
    'latestScores' => collect(),
    'totalHistoryCount' => 0,
])

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
                        <th class="text-start">Date & Time</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Batch</th>
                        <th class="text-center">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($latestScores as $scoreItem)
                        @php
                            $isPassed = (bool) ($scoreItem->pass ?? false);
                            $isFinal = (int) ($scoreItem->final ?? 0) === 1;

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
                            $batchVal = $scoreItem->batch ?? '-';
                        @endphp
                        <tr>
                            <td class="text-start text-muted text-nowrap">{{ $dateFormatted }}</td>
                            <td class="text-center font-weight-bold fw-semibold text-dark">{{ $scoreVal }}</td>
                            <td class="text-center text-muted">{{ $batchVal }}</td>
                            <td class="text-center">
                                @if ($isPassed)
                                    <span class="badge bg-green-lt text-green px-2 py-1">Passed</span>
                                @elseif ($isFinal)
                                    <span class="badge bg-red-lt text-red px-2 py-1">Failed</span>
                                @else
                                    <span class="badge bg-orange-lt text-orange px-2 py-1">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($totalHistoryCount > 5)
                <div class="assessment-score-popover-footer">
                    Showing latest 5 attempts
                </div>
            @endif
        @else
            <div class="assessment-score-popover-empty">
                No assessment results available.
            </div>
        @endif
    </div>
</div>

