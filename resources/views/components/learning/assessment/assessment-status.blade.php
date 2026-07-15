@props([
    'finalResult',
    'isCompleted',
    'isPassed',
])

@if ($isCompleted)
    @if ($isPassed)
        <span class="badge bg-green-lt text-green learning-record-badge assessment-status-badge">Completed</span>
    @else
        <span class="badge bg-red-lt text-red learning-record-badge assessment-status-badge">Failed</span>
    @endif

    <div class="small text-muted mt-1">
        Score: {{ $finalResult->score ?? '-' }}
    </div>
@else
    <span class="badge bg-orange-lt text-orange learning-record-badge assessment-status-badge">Not Started</span>
@endif
