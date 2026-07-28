@props([
    'finalResult',
    'isCompleted',
    'isPassed',
])

@if ($isCompleted)
    @if ($isPassed)
        <span class="badge bg-green-lt text-green learning-record-badge assessment-status-badge">Passed</span>
    @else
        <span class="badge bg-red-lt text-red learning-record-badge assessment-status-badge">Failed</span>
    @endif

    @if ($finalResult->score !== null)
        <div class="small text-muted mt-1 assessment-score">
            Score: {{ $finalResult->score }}
        </div>
    @endif
@else
    <span class="badge bg-orange-lt text-orange learning-record-badge assessment-status-badge">Pending</span>
@endif
