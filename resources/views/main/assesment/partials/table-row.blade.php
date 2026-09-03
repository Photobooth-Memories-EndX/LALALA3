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
        'content' => ($list->assasment_content_for === 'playlist') ? 'Content (Playlist)' : 'Content (All Content)',
        'global' => 'Global',
    ];
    $typeLabel = $typeLabels[$list->type] ?? ucwords($list->type ?? '');

    $hasAQB = count($list->assessment_question_banks) > 0;

    // Bank Question: Pool of questions available in Question Banks attached
    if ($hasAQB) {
        $bankQuestions = $list->assessment_question_banks->sum(function ($aqb) {
            return $aqb->question_bank ? ($aqb->question_bank->questions_count ?? count($aqb->question_bank->questions ?? [])) : 0;
        });
        $bankQuestionDisplay = $bankQuestions;
    } else {
        $bankQuestionDisplay = '-';
    }

    // Used Question: Actual questions used by assessment
    $usedQuestions = $hasAQB
        ? $list->assessment_question_banks->sum('questions_amount')
        : count($list->questions);

    $assessmentUrl = route('assesment.user.view', $list->slug);
@endphp

<tr>
    @php
        $isEligible = ($list->type === 'content' && $list->status === 'OK');
    @endphp
    <td class="text-center">
        @if ($isEligible)
            <input type="checkbox"
                   class="form-check-input assessment-select-checkbox"
                   name="selected_assessments[]"
                   value="{{ $list->id }}"
                   data-title="{{ $list->title }}"
                   data-content-id="{{ $list->content_id }}"
                   data-content-title="{{ $list->content->title ?? ($list->contents->first()->title ?? 'Content ' . $list->content_id) }}"
                   data-type="{{ $list->type }}"
                   data-content-for="{{ $list->assasment_content_for }}"
                   data-trainingplan-id="{{ $list->trainingplan_id ?? '' }}"
                   title="Select for duplicate"
                   data-toggle="tooltip">
        @else
            <input type="checkbox"
                   class="form-check-input assessment-select-checkbox-disabled"
                   disabled
                   title="{{ $list->type !== 'content' ? 'Only Content assessments can be duplicated' : 'Inactive assessments cannot be duplicated' }}"
                   data-toggle="tooltip">
        @endif
    </td>

    <td class="text-center text-muted">
        {{ $iteration }}
    </td>

    <td style="min-width: 280px;">
        <div class="font-weight-bold" style="line-height: 1.4; word-break: break-word;">
            <a href="{{ $assessmentUrl }}" class="text-dark">
                {{ $list->title }}
            </a>
        </div>

        <div class="mt-1 d-flex align-items-center flex-wrap">
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

    <td class="text-center">
        @if ($bankQuestionDisplay !== '-')
            <span class="text-muted font-weight-semibold">
                {{ $bankQuestionDisplay }}
            </span>
        @else
            <span class="text-muted small">-</span>
        @endif
    </td>

    <td class="text-center font-weight-bold text-dark">
        {{ $usedQuestions }}
    </td>

    <td class="text-center">
        <span class="owl-badge owl-badge-orange">
            {{ $list->passing_grade }}
        </span>
    </td>

    @php
        $durationText = '-';
        if ($list->hasTimeLimit() && !empty($list->time_limit_minutes) && (int) $list->time_limit_minutes > 0) {
            $m = (int) $list->time_limit_minutes;
            if ($m < 60) {
                $durationText = $m . ' mins';
            } else {
                $hrs = intdiv($m, 60);
                $remMins = $m % 60;
                $durationText = $hrs . ($hrs > 1 ? ' hrs' : ' hr') . ($remMins > 0 ? ' ' . $remMins . ' mins' : '');
            }
        }
    @endphp

    @php
        $hasFromDate = !empty($list->fromdate);
        $hasToDate = !empty($list->todate);
        $hasPeriod = $list->hasAccessPeriod() && ($hasFromDate || $hasToDate);
    @endphp

    <td style="min-width: 165px;">
        @if ($hasPeriod)
            <div class="assessment-period">
                @if ($hasFromDate)
                    <div class="assessment-period-line">
                        <span class="period-date">
                            <i class="ti ti-calendar"></i>
                            {{ date('d M Y', strtotime($list->fromdate)) }}
                        </span>
                        <span class="period-time">
                            <i class="ti ti-clock"></i>
                            {{ date('H:i', strtotime($list->fromdate)) }}
                        </span>
                    </div>
                @else
                    <div class="period-null">-</div>
                @endif

                @if ($hasToDate)
                    <div class="assessment-period-line">
                        <span class="period-date">
                            <i class="ti ti-calendar"></i>
                            {{ date('d M Y', strtotime($list->todate)) }}
                        </span>
                        <span class="period-time">
                            <i class="ti ti-clock"></i>
                            {{ date('H:i', strtotime($list->todate)) }}
                        </span>
                    </div>
                @else
                    <div class="period-null">-</div>
                @endif
            </div>
        @else
            <span class="text-muted small">-</span>
        @endif
    </td>

    <td class="text-center" style="min-width: 110px;">
        @if ($durationText !== '-')
            <span class="small font-weight-bold text-primary">
                <i class="fas fa-stopwatch mr-1"></i>{{ $durationText }}
            </span>
        @else
            <span class="text-muted small">-</span>
        @endif
    </td>

    <td class="text-center">
        <input
            data-id="{{ $list->id }}"
            data-toggle="toggle"
            class="toggle-class"
            type="checkbox"
            data-onstyle="success"
            data-offstyle="danger"
            data-on="ON"
            data-off="OFF"
            data-size="small"
            data-width="48"
            {{ $list->status == 'OK' ? 'checked' : '' }}>
    </td>

    <td class="text-right">
        @include('main.assesment.partials.action-dropdown', [
            'list' => $list
        ])
    </td>
</tr>