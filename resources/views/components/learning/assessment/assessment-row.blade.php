@props([
    'assessment',
    'index',
])

@php
    $finalResult = null;
    $userAttempts = 0;
    $historyScores = collect();
    $currentUserId = Auth::id();

    if (($assessment->scores ?? null) != null) {
        $userScores = collect($assessment->scores)->filter(function ($scoreItem) use ($currentUserId) {
            return (string) ($scoreItem->user_id ?? '') === (string) $currentUserId;
        });

        $finalResult = $userScores
            ->where('final', 1)
            ->sortByDesc('score')
            ->first();

        if (!$finalResult) {
            $finalResult = $userScores
                ->filter(fn ($s) => $s->score !== null)
                ->sortByDesc('score')
                ->first();
        }

        $userAttempts = $userScores->where('final', 1)->count();
        if ($userAttempts === 0 && $userScores->count() > 0) {
            $userAttempts = $userScores->count();
        }

        $historyScores = $userScores
            ->sortBy(function ($score) {
                if (!empty($score->created_at)) {
                    return is_a($score->created_at, '\DateTimeInterface')
                        ? $score->created_at->getTimestamp()
                        : strtotime((string) $score->created_at);
                }
                return 0;
            })
            ->values();
    }

    $totalHistoryCount = $historyScores->count();
    $historyScores = $historyScores->map(function ($scoreItem, $idx) use ($totalHistoryCount) {
        $scoreItem->attempt_number = $idx + 1;
        $scoreItem->total_attempts = $totalHistoryCount;
        return $scoreItem;
    });
    $latestScores = $historyScores->take(5);

    $isCompleted = $finalResult != null;
    $isPassed = $finalResult && $finalResult->pass;

    $rawType = strtolower((string) ($assessment->type ?? ''));
    $contentFor = strtolower((string) ($assessment->assasment_content_for ?? 'content'));
    $hasTrainingPlanId = !empty($assessment->trainingplan_id);

    $isPlaylistAssessment = ($contentFor === 'playlist') || ($rawType === 'trainingplan') || ($hasTrainingPlanId && $contentFor !== 'content');
    $isContentAssessment = !$isPlaylistAssessment && ($rawType === 'content' || $contentFor === 'content') && !$hasTrainingPlanId;

    $typeLabel = $isPlaylistAssessment
        ? 'Playlist'
        : ($rawType === 'global' ? 'Global' : ($rawType === 'youtube' ? 'MOOC' : ($isContentAssessment ? 'Content' : ucwords(str_replace('_', ' ', $assessment->type ?? '-')))));

    $filterType = $isPlaylistAssessment ? 'playlist' : ($isContentAssessment ? 'content' : ($rawType === 'global' ? 'global' : $rawType));

    $assessmentUrl = !empty($assessment->slug)
        ? route('assesment.user.view', $assessment->slug)
        : null;

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

    $nowCarbon = now();
    $hasAccessPeriod = $assessment->hasAccessPeriod();
    $hasTimeLimit = $assessment->hasTimeLimit();

    if ($hasAccessPeriod && !empty($assessment->fromdate) && !empty($assessment->todate)) {
        $fromDateRaw = strlen(trim((string) $assessment->fromdate)) === 10
            ? trim((string) $assessment->fromdate) . ' 00:00:00'
            : (string) $assessment->fromdate;
        $toDateRaw = strlen(trim((string) $assessment->todate)) === 10
            ? trim((string) $assessment->todate) . ' 23:59:59'
            : (string) $assessment->todate;

        $fromDateCarbon = \Illuminate\Support\Carbon::parse($fromDateRaw);
        $toDateCarbon = \Illuminate\Support\Carbon::parse($toDateRaw);

        $isUpcoming = $nowCarbon->lt($fromDateCarbon);
        $isExpired = $nowCarbon->gt($toDateCarbon);
        $availability = $isUpcoming ? 'upcoming' : ($isExpired ? 'expired' : 'available');

        $fromDateDisplay = $fromDateCarbon->format('d M Y, H:i');
        $toDateDisplay = $toDateCarbon->format('d M Y, H:i');
    } else {
        $isExpired = false;
        $isUpcoming = false;
        $availability = 'available';
        $fromDateDisplay = '-';
        $toDateDisplay = $hasTimeLimit ? ('Time Limit: ' . $assessment->time_limit_minutes . ' mins') : 'No Deadline';
    }

    $totalRemedials = (int) ($assessment->total_remedials ?? 0);
    $maxAttempts = (string) ($assessment->type ?? '') === 'global' ? 1 : (1 + $totalRemedials);
    $scoreDisplay = $finalResult && $finalResult->score !== null ? $finalResult->score : '-';

    $activeDraft = ($assessment->scores ?? null) 
        ? collect($assessment->scores)->where('user_id', $currentUserId)->where('final', 0)->first() 
        : null;

    if (!$activeDraft) {
        $activeDraft = \App\Models\AssesmentResult::where('user_id', $currentUserId)
            ->where('assesment_id', $assessment->id)
            ->where('batch', $assessment->batch)
            ->where('final', 0)
            ->latest('id')
            ->first();
    }

    $hasActiveDraft = ($activeDraft !== null);

    // If active draft exists and time limit is enabled, check timer deadline
    if ($hasActiveDraft && $hasTimeLimit && $activeDraft->created_at) {
        $timeLimitDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
        if ($nowCarbon->gt($timeLimitDeadline)) {
            $isExpired = true;
            $availability = 'expired';
        }
    }

    $answeredCount = ($activeDraft && is_array($activeDraft->user_answer))
        ? count(array_filter($activeDraft->user_answer, fn($v) => $v !== null && $v !== ''))
        : 0;

    $totalCount = ($activeDraft && is_array($activeDraft->question_snapshot) && count($activeDraft->question_snapshot) > 0)
        ? count($activeDraft->question_snapshot)
        : (isset($assessment->questions) ? $assessment->questions->count() : ($assessment->questions_count ?? 10));

    $actionType = $hasActiveDraft
        ? 'continue'
        : ($isCompleted && !$isPassed && (string)($assessment->type ?? '') !== 'global' && $userAttempts < $maxAttempts ? 'remedial' : 'start');
@endphp

<tr class="assessment-row"
    data-assessment-id="{{ $assessment->id }}"
    data-assessment-title="{{ $assessment->title ?? '' }}"
    data-assessment-type="{{ $filterType }}"
    data-assessment-type-label="{{ $typeLabel }}"
    data-assessment-status="{{ $filterStatus }}"
    data-assessment-year="{{ $filterYear }}"
    data-assessment-month="{{ $filterMonth }}"
    data-assessment-date="{{ $filterTimestamp ?: '' }}"
    data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
    data-assessment-grade="{{ $assessment->passing_grade ?? '-' }}"
    data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
    data-assessment-duration="{{ $assessment->duration ?? ($assessment->time_limit_minutes ?? 60) }}"
    data-assessment-from-date="{{ $fromDateDisplay }}"
    data-assessment-to-date="{{ $toDateDisplay }}"
    data-assessment-user-attempts="{{ $userAttempts }}"
    data-assessment-max-attempts="{{ $maxAttempts }}"
    data-assessment-score="{{ $finalResult->score ?? '' }}"
    data-assessment-has-snapshot="{{ $hasActiveDraft ? 'true' : 'false' }}"
    data-assessment-answered-count="{{ $answeredCount }}"
    data-assessment-total-count="{{ $totalCount }}"
    data-assessment-action-type="{{ $actionType }}"
    data-assessment-availability="{{ $availability }}">
    <td class="text-center text-muted align-middle">{{ $index }}</td>

    <td class="text-start align-middle" data-order="{{ mb_strtolower($assessment->title ?? '') }}" data-search="{{ $searchText }}">
        @if ($assessmentUrl)
            <a href="javascript:void(0);"
               role="button"
               class="fw-semibold assessment-title assessment-title-link assessment-name"
               data-assessment-id="{{ $assessment->id }}"
               data-assessment-title="{{ $assessment->title ?? '' }}"
               data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
               data-assessment-type="{{ $filterType }}"
               data-assessment-type-label="{{ $typeLabel }}"
               data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
               data-assessment-from-date="{{ $fromDateDisplay }}"
               data-assessment-to-date="{{ $toDateDisplay }}"
               data-assessment-user-attempts="{{ $userAttempts }}"
               data-assessment-max-attempts="{{ $maxAttempts }}">
                {{ $assessment->title ?? '-' }}
            </a>
        @else
            <div class="fw-semibold assessment-title assessment-name"
                 data-assessment-id="{{ $assessment->id }}"
                 data-assessment-title="{{ $assessment->title ?? '' }}"
                 data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
                 data-assessment-type="{{ $filterType }}"
                 data-assessment-type-label="{{ $typeLabel }}"
                 data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
                 data-assessment-from-date="{{ $fromDateDisplay }}"
                 data-assessment-to-date="{{ $toDateDisplay }}"
                 data-assessment-user-attempts="{{ $userAttempts }}"
                 data-assessment-max-attempts="{{ $maxAttempts }}">
                {{ $assessment->title ?? '-' }}
            </div>
        @endif

        <div class="small assessment-subtitle d-flex align-items-center gap-1 mt-1">
            @if ($isPlaylistAssessment)
                <span class="badge bg-teal-lt text-teal text-uppercase" style="font-size: 0.65rem; font-weight: 600; padding: 2px 6px;">Playlist Assessment</span>
                @if (!empty($assessment->trainingplan->title))
                    <span class="text-muted text-truncate" style="max-width: 250px;">{{ $assessment->trainingplan->title }}</span>
                @endif
            @elseif ($isContentAssessment)
                <span class="badge bg-purple-lt text-purple text-uppercase" style="font-size: 0.65rem; font-weight: 600; padding: 2px 6px;">Content Assessment</span>
                @php
                    $cTitle = $assessment->content->title ?? ($assessment->contents->first()->title ?? null);
                @endphp
                @if (!empty($cTitle))
                    <span class="text-muted text-truncate" style="max-width: 250px;">{{ $cTitle }}</span>
                @endif
            @else
                <span class="badge bg-indigo-lt text-indigo text-uppercase" style="font-size: 0.65rem; font-weight: 600; padding: 2px 6px;">Global Assessment</span>
            @endif
        </div>
    </td>

    <td class="text-start align-middle small lh-sm" data-order="{{ $assessment->fromdate ? strtotime($assessment->fromdate) : 0 }}">
        @if ($hasAccessPeriod && !empty($assessment->fromdate) && !empty($assessment->todate))
            <div>
                <span class="text-muted fw-medium">From:</span> {{ date('d/m/y H:i', strtotime($assessment->fromdate)) }}
            </div>
            <div>
                <span class="text-muted fw-medium">To:</span> {{ date('d/m/y H:i', strtotime($assessment->todate)) }}
            </div>
        @endif

        @if ($hasTimeLimit)
            <div class="{{ $hasAccessPeriod ? 'mt-1' : '' }}">
                <span class="badge bg-blue-lt text-blue"><i class="ti ti-clock me-1"></i>{{ $assessment->time_limit_minutes }} mins</span>
            </div>
        @elseif (!$hasAccessPeriod)
            <span class="text-muted">No Deadline</span>
        @endif
    </td>

    <td class="text-center align-middle">
        <x-learning.assessment.assessment-type
            :type="$assessment->type"
            :label="$typeLabel"
        />
    </td>

    <td class="text-center align-middle score-column">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="d-inline-flex align-items-center justify-content-center assessment-score-trigger score-box position-relative"
                 tabindex="0"
                 role="button"
                 aria-haspopup="true"
                 aria-expanded="false">
                <span class="score-value">
                    {{ $scoreDisplay }}
                </span>

                <div class="d-none assessment-score-popover-template">
                    <x-learning.assessment.assessment-score-popover
                        :latest-scores="$latestScores"
                        :total-history-count="$totalHistoryCount"
                        :assessment-type="$assessment->type"
                        :assessment-batch="$finalResult->batch ?? ($assessment->batch ?? null)"
                        :passing-grade="$assessment->passing_grade"
                    />
                </div>
            </div>
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
            :is-expired="$isExpired"
            :has-snapshot="$hasActiveDraft"
            :answered-count="$answeredCount"
            :total-count="$totalCount"
            :duration="$assessment->duration ?? ($assessment->time_limit_minutes ?? 60)"
            :user-attempts="$userAttempts"
            :max-attempts="$maxAttempts"
            :total-remedials="$totalRemedials"
            :assessment-id="$assessment->id"
            :assessment-title="$assessment->title ?? ''"
            :assessment-batch="$finalResult->batch ?? ($assessment->batch ?? '-')"
            :assessment-type="$filterType"
            :type-label="$typeLabel"
            :passing-grade="$assessment->passing_grade ?? '-'"
            :from-date="$fromDateDisplay"
            :to-date="$toDateDisplay"
        />
    </td>
</tr>
