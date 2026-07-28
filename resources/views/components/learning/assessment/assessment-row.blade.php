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
    <td class="text-muted">{{ $index }}</td>

    <td data-order="{{ mb_strtolower($assessment->title ?? '') }}" data-search="{{ $searchText }}">
        @if ($assessmentUrl)
            <a href="{{ $assessmentUrl }}"
               class="fw-semibold text-primary assessment-title assessment-title-link">
                {{ $assessment->title ?? '-' }}
            </a>
        @else
            <div class="fw-semibold text-primary assessment-title">{{ $assessment->title ?? '-' }}</div>
        @endif

        {{-- Jika subtitle juga ingin diubah menjadi biru muda/muted, bisa gunakan text-primary-lt (jika pakai Tabler) --}}
        {{-- Atau jika ingin tetap abu-abu biarkan text-muted, namun di bawah ini saya ubah ke text-primary agar semuanya biru --}}
        <div class="text-primary small opacity-75 assessment-subtitle">
            {{ $assessment->trainingplan->title ?? 'Assessment' }}
        </div>
    </td>

    <td data-order="{{ $assessment->fromdate ? strtotime($assessment->fromdate) : 0 }}">
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
        <x-learning.assessment.assessment-type
            :type="$assessment->type"
            :label="$typeLabel"
        />
    </td>

    <td>{{ $finalResult->batch ?? '-' }}</td>
    <td>{{ $finalResult && $finalResult->score !== null ? $finalResult->score : '-' }}</td>

    <td>
        <x-learning.assessment.assessment-status
            :final-result="$finalResult"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
            :passing-grade="$assessment->passing_grade"
        />
    </td>

    <td class="text-end">
        <x-learning.assessment.assessment-action
            :url="$assessmentUrl"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
        />
    </td>
</tr>
