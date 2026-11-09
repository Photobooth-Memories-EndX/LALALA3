@props([
    'assessments',
    'result',
])

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <h3 class="card-title mb-1">Assessment List</h3>
        <div class="text-muted small">Your Available Assessments.</div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="assessmentTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Assessment</th>
                    <th>Datetime</th>
                    <th>Type</th>
                    <th>Batch</th>
                    <th>Passing Grade</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($assessments as $assessment)
                    @php
                        $finalResult = null;

                        if (($assessment->scores ?? null) != null) {
                            $userScores = collect($assessment->scores)->filter(function ($scoreItem) {
                                return (string) ($scoreItem->user_id ?? '') === (string) Auth::id();
                            });

                            $finalResult = $userScores
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
                        <td>{{ $loop->iteration }}</td>

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
                            @if ($isCompleted)
                                @if ($isPassed)
                                    <span class="badge bg-green-lt text-green">Completed</span>
                                @else
                                    <span class="badge bg-red-lt text-red">Failed</span>
                                @endif

                                <div class="small text-muted mt-1">
                                    Score: {{ $finalResult->score ?? '-' }}
                                </div>
                            @else
                                <span class="badge bg-orange-lt text-orange">Pending</span>
                            @endif
                        </td>

                        <td class="text-end">
                            @if ($isCompleted && $isPassed)
                                <button class="btn btn-sm btn-white" disabled>
                                    Completed
                                </button>
                            @else
                                @if (!empty($assessment->slug))
                                    <a href="{{ route('assesment.user.view', $assessment->slug) }}"
                                       class="btn btn-sm btn-primary">
                                        {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Unavailable
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <h3 class="card-title mb-1">Assessment Result</h3>
        <div class="text-muted small">Your assessment history.</div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="assessmentResultTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Datetime</th>
                    <th>Assessment</th>
                    <th>Score</th>
                    <th>Batch</th>
                    <th>Result</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($result as $list)
                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>
                            {{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}
                        </td>

                        <td>{{ $list->assesment->title ?? '-' }}</td>
                        <td>{{ $list->score ?? '-' }}</td>
                        <td>{{ $list->batch ?? '-' }}</td>

                        <td>
                            @if ($list->pass)
                                <span class="badge bg-green-lt text-green">Pass</span>
                            @else
                                <span class="badge bg-red-lt text-red">Failed</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
