@php
    if ($list->type == 'content') {
        $typeClass = 'owl-badge-blue';
    } elseif ($list->type == 'trainingplan') {
        $typeClass = 'owl-badge-purple';
    } elseif ($list->type == 'global') {
        $typeClass = 'owl-badge-green';
    } else {
        $typeClass = 'badge-secondary';
    }

    $typeLabels = [
        'trainingplan' => 'Playlist',
        'content' => 'Content',
        'global' => 'Global',
    ];
    $typeLabel = $typeLabels[$list->type] ?? ucwords($list->type ?? '');

    $totalQuestions = count($list->assessment_question_banks) > 0
        ? $list->assessment_question_banks->sum('questions_amount')
        : count($list->questions);

    $assessmentUrl = route('assesment.user.view', $list->slug);
@endphp

<tr>
    <td class="text-center text-muted">
        {{ $iteration }}
    </td>

    <td style="min-width: 420px;">
        <div class="font-weight-bold">
            <a href="{{ $assessmentUrl }}" class="text-dark">
                {{ $list->title }}
            </a>
        </div>

        <div class="mt-2 d-flex align-items-center flex-wrap">
            <span class="text-muted small mr-1">
                Unique Code:
            </span>

            <span class="owl-code mr-1">
                {{ $list->unique_code ?? '#N/A' }}
            </span>

            <button
                type="button"
                class="btn btn-sm btn-light btn-copy-assessment"
                data-copy-text="{{ $list->unique_code }}"
                data-copy-label="Assessment Code"
                data-toggle="tooltip"
                title="Copy assessment code">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    </td>

    <td class="text-center">
        <span class="owl-badge {{ $typeClass }}">
            {{ $typeLabel }}
        </span>
    </td>

    <td class="text-center font-weight-bold">
        {{ $totalQuestions }}
    </td>

    <td class="text-center">
        <span class="owl-badge owl-badge-orange">
            {{ $list->passing_grade }}
        </span>
    </td>

    <td style="min-width: 185px;">
        @if ($list->hasAccessPeriod() && $list->fromdate && $list->todate)
            <div class="small">
                <div>
                    <i class="far fa-calendar-alt mr-1 text-primary"></i>
                    {{ date('d M Y H:i', strtotime($list->fromdate)) }}
                </div>

                <div class="text-muted mt-1">
                    <i class="far fa-clock mr-1"></i>
                    {{ date('d M Y H:i', strtotime($list->todate)) }}
                </div>
            </div>
        @endif

        @if ($list->hasTimeLimit())
            <div class="small fw-semibold text-primary {{ $list->hasAccessPeriod() ? 'mt-1' : '' }}">
                <i class="fas fa-stopwatch mr-1"></i>
                {{ $list->time_limit_minutes }} Mins Time Limit
            </div>
        @elseif (!$list->hasAccessPeriod())
            <span class="text-muted small">
                No Deadline
            </span>
        @endif
    </td>

    <td class="text-center">
        <input
            data-id="{{ $list->id }}"
            data-toggle="toggle"
            class="toggle-class"
            type="checkbox"
            data-onstyle="success"
            data-offstyle="secondary"
            data-on="ON"
            data-off="OFF"
            data-size="small"
            data-width="48"
            {{ $list->status == 'OK' ? 'checked' : '' }}>
    </td>

    <td class="text-center">
        <a
            href="{{ route('assesment.assign.users.index', $list->slug) }}"
            class="btn btn-sm btn-outline-secondary owl-action-btn"
            data-toggle="tooltip"
            title="Assign users">
            <i class="fas fa-users"></i>
        </a>
    </td>

    <td class="text-right">
        @include('main.assesment.partials.action-dropdown', [
            'list' => $list
        ])
    </td>
</tr>