@props([
    'finalResult',
    'isCompleted',
    'isPassed',
    'passingGrade' => null,
])

<div class="d-flex flex-column align-items-center justify-content-center">
    @if ($isCompleted)
        @if ($isPassed)
            <span class="badge bg-green-lt text-green learning-record-badge assessment-status-badge assessment-status-passed">Passed</span>
        @else
            <span class="badge bg-red-lt text-red learning-record-badge assessment-status-badge assessment-status-failed">Failed</span>
        @endif
    @else
        <span class="badge bg-orange-lt text-orange learning-record-badge assessment-status-badge assessment-status-pending">Pending</span>
    @endif

    @if ($passingGrade !== null && $passingGrade !== '')
        <div class="small text-muted assessment-passing-grade">
            Passing Grade : {{ $passingGrade }}
        </div>
    @endif
</div>
