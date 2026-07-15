@props([
    'finalResult',
    'isCompleted',
    'isPassed',
])

@if ($isCompleted)
    @if ($isPassed)
        <span class="badge bg-green-lt text-green">Completed</span>
    @else
        <span class="badge bg-red-lt text-red">Failed</span>
    @endif

    <div class="small text-muted mt-1">
        Score: {{ $finalResult->score ?? '-' }}
    </div>
@else
    <span class="badge bg-orange-lt text-orange">Not Started</span>
@endif
