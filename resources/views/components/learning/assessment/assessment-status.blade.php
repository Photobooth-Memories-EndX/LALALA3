@props([
    'finalResult',
    'isCompleted',
    'isPassed',
    'passingGrade' => null,
])

@if ($isCompleted)
    @if ($isPassed)
        <span class="badge bg-green-lt text-green learning-record-badge assessment-status-badge">Passed</span>
    @else
        <span class="badge bg-red-lt text-red learning-record-badge assessment-status-badge">Failed</span>

        @if ($passingGrade !== null)
            <div class="small text-muted mt-1 assessment-passing-grade">
                Passing Grade: {{ $passingGrade }}
            </div>
        @endif
    @endif
@else
    <span class="badge bg-orange-lt text-orange learning-record-badge assessment-status-badge">Pending</span>
@endif
