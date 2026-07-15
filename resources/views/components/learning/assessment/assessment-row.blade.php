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
@endphp

<tr class="assessment-row">
    <td class="text-muted">{{ $index }}</td>

    <td data-order="{{ mb_strtolower($assessment->title ?? '') }}">
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
            :url="$assessmentUrl"
            :is-completed="$isCompleted"
            :is-passed="$isPassed"
        />
    </td>
</tr>
