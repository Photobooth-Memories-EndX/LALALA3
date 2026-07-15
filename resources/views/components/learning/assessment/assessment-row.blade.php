@props([
    'assessment',
    'index',
])

@php
    $finalResult = null;

    if (($assessment->scores ?? null) != null) {
        $finalResult = $assessment->scores
            ->where('user_id', Auth::user()->id)
            ->where('final', 1)
            ->sortByDesc('score')
            ->first();
    }

    $isCompleted = $finalResult != null;
    $isPassed = $finalResult && $finalResult->pass;

    $typeLabel = $assessment->type == 'trainingplan'
        ? 'Playlist'
        : ucwords(str_replace('_', ' ', $assessment->type ?? '-'));
@endphp

<tr>
    <td>{{ $index }}</td>

    <td>
        <div class="fw-semibold">{{ $assessment->title ?? '-' }}</div>
        <div class="text-muted small">
            {{ $assessment->trainingplan->title ?? 'Assessment' }}
        </div>
    </td>

    <td>
        @if (($assessment->timer ?? 'n') == 'y')
            <div class="small">
                From: {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}
            </div>
            <div class="small">
                To: {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}
            </div>
        @else
            -
        @endif
    </td>

    <td>
        <span class="badge bg-blue-lt text-blue">
            {{ $typeLabel }}
        </span>
    </td>

    <td>{{ $assessment->batch ?? '-' }}</td>
    <td>{{ $assessment->passing_grade ?? '-' }}</td>

    <td>
        <x-learning.assessment.assessment-status
            :final-result="$finalResult"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
        />
    </td>

    <td class="text-end">
        <x-learning.assessment.assessment-action
            :assessment="$assessment"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
        />
    </td>
</tr>
