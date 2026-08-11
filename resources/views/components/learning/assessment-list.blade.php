@props([
    'assessments',
])

@php
    $assessmentItems = collect($assessments ?? []);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h3 class="card-title mb-1">Assessment List</h3>
                <div class="text-muted small">
                    Pilih assessment yang tersedia dan lihat status pengerjaan Anda.
                </div>
            </div>

            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text"
                           id="assessmentSearch"
                           class="form-control rounded-3"
                           placeholder="Cari assessment">
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        @if ($assessmentItems->count() > 0)
            <table class="table table-vcenter table-hover card-table mb-0" id="table-list-assessment">
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
                    @foreach ($assessmentItems as $assessment)
                        @php
                            $bestScore = null;
                            $finalResult = null;

                            if (($assessment->scores ?? null) != null) {
                                $userScores = collect($assessment->scores)->filter(function ($scoreItem) {
                                    return (string) ($scoreItem->user_id ?? '') === (string) Auth::id();
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

                                $bestScore = $finalResult->score ?? null;
                            }

                            $isCompleted = $finalResult != null;
                            $isPassed = $finalResult && $finalResult->pass;

                            $typeLabel = $assessment->type == 'trainingplan'
                                ? 'Playlist'
                                : ucwords(str_replace('_', ' ', $assessment->type ?? '-'));
                        @endphp

                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>

                            <td style="min-width: 280px;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-sm bg-blue-lt text-blue">
                                        {{ strtoupper(substr($assessment->title ?? 'A', 0, 1)) }}
                                    </span>

                                    <div>
                                        <div class="fw-semibold line-clamp-2">
                                            {{ $assessment->title ?? '-' }}
                                        </div>

                                        @if (($assessment->type ?? '') == 'trainingplan')
                                            <div class="small text-muted">
                                                {{ $assessment->trainingplan->title ?? 'Playlist Assessment' }}
                                            </div>
                                        @elseif (($assessment->type ?? '') == 'content')
                                            <div class="small text-muted">
                                                Content Assessment
                                            </div>
                                        @else
                                            <div class="small text-muted">
                                                Global Assessment
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td style="min-width: 160px;">
                                @if (($assessment->timer ?? 'n') == 'y')
                                    <div class="small">
                                        <span class="text-muted">From:</span>
                                        {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}
                                    </div>

                                    <div class="small">
                                        <span class="text-muted">To:</span>
                                        {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-blue-lt text-blue">
                                    {{ $typeLabel }}
                                </span>
                            </td>

                            <td>{{ $assessment->batch ?? '-' }}</td>

                            <td>{{ $assessment->passing_grade ?? '-' }}</td>

                            <td style="min-width: 130px;">
                                @if ($isCompleted)
                                    @if ($isPassed)
                                        <span class="badge bg-green-lt text-green">Completed</span>
                                    @else
                                        <span class="badge bg-red-lt text-red">Failed</span>
                                    @endif

                                    <div class="small text-muted mt-1">
                                        Score: {{ $bestScore ?? '-' }}
                                    </div>
                                @else
                                    <span class="badge bg-orange-lt text-orange">Pending</span>
                                @endif
                            </td>

                            <td class="text-end">
                                @if ($isCompleted && $isPassed)
                                    <button type="button" class="btn btn-sm btn-white" disabled>
                                        Completed
                                    </button>
                                @else
                                    @if (!empty($assessment->slug))
                                        <a href="{{ route('assesment.user.view', $assessment->slug) }}"
                                           class="btn btn-sm btn-primary">
                                            {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            Unavailable
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty py-5">
                <div class="empty-title">Belum ada assessment</div>
                <div class="empty-subtitle text-muted">
                    Assessment yang tersedia akan muncul di halaman ini.
                </div>
            </div>
        @endif
    </div>
</div>
