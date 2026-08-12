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
    $now = time();
    $fromDateRaw = !empty($assessment->fromdate) ? (strlen(trim((string) $assessment->fromdate)) === 10 ? trim((string) $assessment->fromdate) . ' 23:59:59' : (string) $assessment->fromdate) : null;
    $toDateRaw = !empty($assessment->todate) ? (strlen(trim((string) $assessment->todate)) === 10 ? trim((string) $assessment->todate) . ' 23:59:59' : (string) $assessment->todate) : null;
    $fromDate = $fromDateRaw ? strtotime($fromDateRaw) : null;
    $toDate = $toDateRaw ? strtotime($toDateRaw) : null;

    $isExpired = $toDate ? ($now > $toDate) : false;
    $isUpcoming = $fromDate ? ($now < $fromDate) : false;
    $availability = $isUpcoming ? 'upcoming' : ($isExpired ? 'expired' : 'available');

    $totalRemedials = (int) ($assessment->total_remedials ?? 0);
    $maxAttempts = 1 + $totalRemedials;
    $scoreDisplay = $finalResult && $finalResult->score !== null ? $finalResult->score : '-';

    $fromDateDisplay = (($assessment->timer ?? 'n') == 'y' && !empty($assessment->fromdate))
        ? date('d M Y, H:i', strtotime($assessment->fromdate))
        : '-';
    $toDateDisplay = (($assessment->timer ?? 'n') == 'y' && !empty($assessment->todate))
        ? date('d M Y, H:i', strtotime($assessment->todate))
        : '-';
@endphp

<tr class="assessment-row"
    data-assessment-id="{{ $assessment->id }}"
    data-assessment-title="{{ $assessment->title ?? '' }}"
    data-assessment-type="{{ $assessment->type ?? '' }}"
    data-assessment-type-label="{{ $typeLabel }}"
    data-assessment-status="{{ $filterStatus }}"
    data-assessment-year="{{ $filterYear }}"
    data-assessment-month="{{ $filterMonth }}"
    data-assessment-date="{{ $filterTimestamp ?: '' }}"
    data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
    data-assessment-grade="{{ $assessment->passing_grade ?? '-' }}"
    data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
    data-assessment-from-date="{{ $fromDateDisplay }}"
    data-assessment-to-date="{{ $toDateDisplay }}"
    data-assessment-user-attempts="{{ $userAttempts }}"
    data-assessment-max-attempts="{{ $maxAttempts }}"
    data-assessment-score="{{ $finalResult->score ?? '' }}"
    data-assessment-availability="{{ $availability }}">
    <td class="text-center text-muted align-middle">{{ $index }}</td>

    <td class="text-start align-middle" data-order="{{ mb_strtolower($assessment->title ?? '') }}" data-search="{{ $searchText }}">
        @if ($assessmentUrl)
            <a href="javascript:void(0);"
               role="button"
               class="fw-semibold text-primary assessment-title assessment-title-link assessment-name"
               data-assessment-id="{{ $assessment->id }}"
               data-assessment-title="{{ $assessment->title ?? '' }}"
               data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
               data-assessment-type="{{ $assessment->type ?? '' }}"
               data-assessment-type-label="{{ $typeLabel }}"
               data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
               data-assessment-from-date="{{ $fromDateDisplay }}"
               data-assessment-to-date="{{ $toDateDisplay }}"
               data-assessment-user-attempts="{{ $userAttempts }}"
               data-assessment-max-attempts="{{ $maxAttempts }}">
                {{ $assessment->title ?? '-' }}
            </a>
        @else
            <div class="fw-semibold text-primary assessment-title assessment-name"
                 data-assessment-id="{{ $assessment->id }}"
                 data-assessment-title="{{ $assessment->title ?? '' }}"
                 data-assessment-batch="{{ $finalResult->batch ?? ($assessment->batch ?? '-') }}"
                 data-assessment-type="{{ $assessment->type ?? '' }}"
                 data-assessment-type-label="{{ $typeLabel }}"
                 data-assessment-passing-grade="{{ $assessment->passing_grade ?? '-' }}"
                 data-assessment-from-date="{{ $fromDateDisplay }}"
                 data-assessment-to-date="{{ $toDateDisplay }}"
                 data-assessment-user-attempts="{{ $userAttempts }}"
                 data-assessment-max-attempts="{{ $maxAttempts }}">
                {{ $assessment->title ?? '-' }}
            </div>
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
            :user-attempts="$userAttempts"
            :max-attempts="$maxAttempts"
            :total-remedials="$totalRemedials"
            :assessment-id="$assessment->id"
            :assessment-title="$assessment->title ?? ''"
            :assessment-batch="$finalResult->batch ?? ($assessment->batch ?? '-')"
            :assessment-type="$assessment->type ?? ''"
            :type-label="$typeLabel"
            :passing-grade="$assessment->passing_grade ?? '-'"
            :from-date="$fromDateDisplay"
            :to-date="$toDateDisplay"
        />
    </td>
</tr>
