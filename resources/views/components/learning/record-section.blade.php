@props([
    'contentUser',
    'averageProgress' => 0,
    'certificateSettings' => collect(),
])

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
        <h3 class="mb-1">Learning Record</h3>
        <div class="text-muted">View your completed learning history.</div>

        <div class="mt-4">
            <div class="fw-bold mb-1">Average Progress</div>
            <div class="display-6 text-primary mb-2">{{ $averageProgress }}%</div>
            <div class="progress progress-sm">
                <div class="progress-bar bg-primary" style="width: {{ $averageProgress }}%"></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <h3 class="card-title mb-1">Training History</h3>
        <div class="text-muted small">
            Completed training programs and certificate status.
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table mb-0" id="recordTable">
                <thead>
                    <tr>
                        <th class="text-muted">No</th>
                        <th class="text-muted">Certificate</th>
                        <th class="text-muted">Class</th>
                        <th class="text-muted">Type</th>
                        <th class="text-muted">Classification</th>
                        <th class="text-muted">Duration</th>
                        <th class="text-muted">Progress</th>
                        <th class="text-muted">Finish Date</th>
                        <th class="text-muted">Score Test</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        // Pre-sort: records eligible for certificate appear first (priority 1),
                        // then Not Available (priority 0). Within each group, newest first.
                        $sortedContentUser = collect($contentUser)
                            ->filter(fn ($cu) => ($cu->finish ?? '0') == '1')
                            ->sortByDesc(function ($cu) use ($certificateSettings) {
                                $content  = $cu->content ?? null;
                                $progress = (int) ($cu->progress ?? 0);

                                $certSetting = collect($certificateSettings)
                                    ->where('certificate_type', 'content')
                                    ->where('content_id', $content->id ?? null)
                                    ->first();

                                if (!$certSetting || !$certSetting->is_active) {
                                    return [0, 0];
                                }

                                $minProgress = $certSetting->minimum_progress ?? null;
                                $minScore    = $certSetting->minimum_score ?? null;

                                $progressOk = is_null($minProgress) || (int) $minProgress <= 0
                                    || $progress >= (int) $minProgress;

                                $score = null;
                                if (!blank($cu->assesment ?? null)) {
                                    $top = ($cu->assesment->scores ?? collect())
                                        ->filter(fn ($r) => $r->user_id == $cu->user_id)
                                        ->sortByDesc('score')
                                        ->first();
                                    $score = $top->score ?? null;
                                }

                                $scoreOk = is_null($minScore) || (int) $minScore <= 0
                                    || (!is_null($score) && $score >= (int) $minScore);

                                $eligible = ($progressOk && $scoreOk) ? 1 : 0;

                                // Secondary sort: newest updated_at within each group
                                $ts = optional($cu->updated_at)->timestamp ?? 0;

                                return [$eligible, $ts];
                            })
                            ->values();
                    @endphp
                    @foreach ($sortedContentUser as $list)
                            @php
                                $content = $list->content ?? null;
                                $progress = (int) ($list->progress ?? 0);
                                $score = null;

                                if (!blank($list->assesment ?? null)) {
                                    $datas = $list->assesment->scores ?? collect();

                                    $filter = $datas->filter(function ($assesment_result) use ($list) {
                                        return $assesment_result->user_id == $list->user_id;
                                    })->sortByDesc('score')->first();

                                    if (!blank($filter)) {
                                        $score = $filter->score;
                                    }
                                }

                                $type = $content->type ?? 'Content';

                                $certificateSetting = collect($certificateSettings)
                                    ->where('certificate_type', 'content')
                                    ->where('content_id', $content->id ?? null)
                                    ->first();

                                $minimumProgress = $certificateSetting->minimum_progress ?? null;
                                $minimumScore = $certificateSetting->minimum_score ?? null;

                                $isProgressPassed = true;
                                $isScorePassed = true;

                                if (!is_null($minimumProgress) && (int) $minimumProgress > 0) {
                                    $isProgressPassed = $progress >= (int) $minimumProgress;
                                }

                                if (!is_null($minimumScore) && (int) $minimumScore > 0) {
                                    $isScorePassed = !is_null($score)
                                        && $score >= (int) $minimumScore;
                                }

                                $canGenerateCertificate = $certificateSetting
                                    && $certificateSetting->is_active
                                    && $isProgressPassed
                                    && $isScorePassed;
                            @endphp

                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>

                                <td class="text-nowrap">
                                    @if($canGenerateCertificate)
                                        <a href="{{ route('certificate.generate', $list->id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="ti ti-certificate me-1"></i>
                                            Sertifikat
                                        </a>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">
                                            Not Available
                                        </span>
                                    @endif
                                </td>

                                <td style="min-width: 280px;">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm mr-2 {{ in_array($type, ['Video', 'YouTube']) ? 'bg-red-lt text-red' : 'bg-yellow-lt text-yellow' }}">
                                            @if (in_array($type, ['Video', 'YouTube']))
                                                <i class="ti ti-video"></i>
                                            @else
                                                <i class="ti ti-file-description"></i>
                                            @endif
                                        </span>

                                        <div>
                                            @if ($content && !empty($content->slug))
                                                <a href="{{ route('content.user.view', $content->slug) }}"
                                                   class="fw-semibold text-reset line-clamp-2">
                                                    {{ $content->title ?? '-' }}
                                                </a>
                                            @else
                                                <div class="fw-semibold line-clamp-2">
                                                    {{ $content->title ?? '-' }}
                                                </div>
                                            @endif

                                            <div class="text-muted small">
                                                {{ ucfirst($list->status ?? '-') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-blue-lt text-blue">
                                        {{ $type }}
                                    </span>
                                </td>

                                <td style="min-width: 180px;">
                                    {{ $content->classification->classification_name ?? '-' }}
                                </td>

                                <td>{{ $content->duration ?? '-' }}</td>

                                <td style="min-width: 170px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $progress }}%</span>

                                        @if(!is_null($minimumProgress) && (int) $minimumProgress > 0)
                                            <span class="{{ $isProgressPassed ? 'text-success' : 'text-danger' }}">
                                                Min {{ $minimumProgress }}%
                                            </span>
                                        @endif
                                    </div>

                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $isProgressPassed ? 'bg-success' : 'bg-danger' }}"
                                             style="width: {{ min($progress, 100) }}%">
                                        </div>
                                    </div>
                                </td>

                                <td style="min-width: 150px;">
                                    {{ $list->updated_at ? date('Y-m-d H:i', strtotime($list->updated_at)) : '-' }}
                                </td>

                                <td style="min-width: 120px;">
                                    @if(!is_null($score))
                                        <span class="badge {{ $isScorePassed ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                                            {{ $score }}
                                        </span>

                                        @if(!is_null($minimumScore) && (int) $minimumScore > 0)
                                            <div class="small text-muted mt-1">
                                                Min {{ $minimumScore }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">
                                            -
                                        </span>

                                        @if(!is_null($minimumScore) && (int) $minimumScore > 0)
                                            <div class="small text-danger mt-1">
                                                Butuh score min {{ $minimumScore }}
                                            </div>
                                        @endif
                                    @endif
                                </td>

                            </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
