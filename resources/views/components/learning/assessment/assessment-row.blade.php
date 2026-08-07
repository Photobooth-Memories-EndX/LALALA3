@props([
    'assessment',
    'index',
])

@php
    $finalResult = null;
    $userAttempts = 0;

    if (($assessment->scores ?? null) != null) {
        $userScores = $assessment->scores->where('user_id', Auth::user()->id);
        $finalResult = $userScores
            ->where('final', 1)
            ->sortByDesc('score')
            ->first();
        $userAttempts = $userScores->where('final', 1)->count();
    }

    $isCompleted = $finalResult != null;
    $isPassed = $finalResult && $finalResult->pass;

    $typeLabel = match (strtolower((string) ($assessment->type ?? ''))) {
        'trainingplan', 'playlist' => 'Playlist',
        'youtube'                  => 'MOOC',
        default                    => ucwords(str_replace('_', ' ', $assessment->type ?? '-')),
    };

    $assessmentUrl = !empty($assessment->slug)
        ? route('assesment.user.view', $assessment->slug)
        : null;

    $filterType = match (strtolower((string) ($assessment->type ?? ''))) {
        'trainingplan', 'playlist' => 'playlist',
        'content' => 'content',
        'global' => 'global',
        default => strtolower((string) ($assessment->type ?? '')),
    };
    $filterStatus = $isPassed ? 'passed' : ($isCompleted ? 'failed' : 'not_started');
    $filterDate = $assessment->fromdate ?? null;
    $filterTimestamp = $filterDate ? strtotime((string) $filterDate) : false;
    $filterYear = $filterTimestamp ? date('Y', $filterTimestamp) : '';
    $filterMonth = $filterTimestamp ? date('n', $filterTimestamp) : '';
    $contentTitles = collect($assessment->contents ?? [])->pluck('title')->filter();
    $searchText = collect([
        $assessment->title ?? null,
        $assessment->trainingplan->title ?? null,
    ])->merge($contentTitles)->filter()->implode(' ');
    $today = strtotime(date('Y-m-d'));
    $fromDate = !empty($assessment->fromdate) ? strtotime(date('Y-m-d', strtotime($assessment->fromdate))) : null;
    $toDate = !empty($assessment->todate) ? strtotime(date('Y-m-d', strtotime($assessment->todate))) : null;
    $availability = $fromDate && $today < $fromDate
        ? 'upcoming'
        : ($toDate && $today > $toDate ? 'expired' : 'available');

    $totalRemedials = (int) ($assessment->total_remedials ?? 0);
    $scoreDisplay = $finalResult && $finalResult->score !== null ? $finalResult->score : '-';
@endphp

<tr class="assessment-row"
    data-assessment-type="{{ $filterType }}"
    data-assessment-status="{{ $filterStatus }}"
    data-assessment-year="{{ $filterYear }}"
    data-assessment-month="{{ $filterMonth }}"
    data-assessment-date="{{ $filterTimestamp ?: '' }}"
    data-assessment-batch="{{ $finalResult->batch ?? '' }}"
    data-assessment-grade="{{ $assessment->passing_grade ?? '' }}"
    data-assessment-score="{{ $finalResult->score ?? '' }}"
    data-assessment-availability="{{ $availability }}">
    <td class="text-center text-muted align-middle">{{ $index }}</td>

    <td class="text-start align-middle" data-order="{{ mb_strtolower($assessment->title ?? '') }}" data-search="{{ $searchText }}">
        @if ($assessmentUrl)
            <a href="{{ $assessmentUrl }}"
               class="fw-semibold text-primary assessment-title assessment-title-link">
                {{ $assessment->title ?? '-' }}
            </a>
        @else
            <div class="fw-semibold text-primary assessment-title">{{ $assessment->title ?? '-' }}</div>
        @endif

        <div class="text-primary small opacity-75 assessment-subtitle">
            {{ $assessment->trainingplan->title ?? 'Assessment' }}
        </div>
    </td>

    <td class="text-start align-middle small lh-sm" data-order="{{ $assessment->fromdate ? strtotime($assessment->fromdate) : 0 }}">
        @if (($assessment->timer ?? 'n') == 'y')
            <div>
                <span class="text-muted fw-medium">From:</span> {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}
            </div>
            <div>
                <span class="text-muted fw-medium">To:</span> {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}
            </div>
        @else
            -
        @endif
    </td>

    <td class="text-center align-middle">
        <x-learning.assessment.assessment-type
            :type="$assessment->type"
            :label="$typeLabel"
        />
    </td>

    <td class="text-center align-middle">
        <div class="d-flex justify-content-center align-items-center h-100">
            {{ $finalResult->batch ?? '-' }}
        </div>
    </td>

    <td class="text-center align-middle">
        <div class="d-flex flex-column align-items-center justify-content-center gap-1">
            <div class="fw-semibold text-dark">
                {{ $scoreDisplay }}
            </div>
            @if ($totalRemedials > 0)
                <span class="badge border rounded-pill bg-white text-dark px-2 py-0 small">
                    {{ $userAttempts }}/{{ $totalRemedials }}
                </span>
            @else
                <span class="badge border rounded-pill bg-light text-secondary px-2 py-0 small">
                    -
                </span>
            @endif
        </div>
    </td>

    <td class="text-center align-middle">
        <x-learning.assessment.assessment-status
            :final-result="$finalResult"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
            :passing-grade="$assessment->passing_grade"
        />
    </td>

    <td class="text-center align-middle">
        <x-learning.assessment.assessment-action
            :url="$assessmentUrl"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
        />
    </td>
</tr>
