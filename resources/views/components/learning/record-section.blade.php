@props([
    'contentUser',
    'averageProgress' => 0,
    'certificateSettings' => collect(),
])

{{-- <div class="card border-0 shadow-sm rounded-4 mb-3">
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
</div> --}}

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <h3 class="card-title mb-1">Training History</h3>
        <div class="text-muted small">
            Completed training programs and certificate status.
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table mb-0 learning-record-table" id="recordTable">
                <thead>
                    <tr>
                        <th class="text-muted" style="width: 4%">No</th>
                        <th class="text-muted" style="width: 26%">Class</th>
                        <th class="text-muted" style="width: 8%">Type</th>
                        <th class="text-muted" style="width: 13%">Classification</th>
                        <th class="text-muted" style="width: 8%">Duration</th>
                        <th class="text-muted" style="width: 9%">Progress</th>
                        <th class="text-muted" style="width: 12%">Finish Date</th>
                        <th class="text-muted" style="width: 9%">Score Test</th>
                        <th class="text-muted" style="width: 11%">Certificate</th>
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

                                $typeBadgeClass = match (true) {
                                    in_array($type, ['Video', 'YouTube']) => 'bg-blue-lt text-blue',
                                    in_array($type, ['Quiz', 'Assessment', 'Assesment']) => 'bg-purple-lt text-purple',
                                    default => 'bg-yellow-lt text-yellow',
                                };

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

                                

                                <td>
                                    @if ($content && !empty($content->slug))
                                        <a href="{{ route('content.user.view', $content->slug) }}"
                                           class="fw-semibold text-reset learning-record-title"
                                           title="{{ $content->title ?? '-' }}">
                                            {{ $content->title ?? '-' }}
                                        </a>
                                    @else
                                        <div class="fw-semibold learning-record-title"
                                             title="{{ $content->title ?? '-' }}">
                                            {{ $content->title ?? '-' }}
                                        </div>
                                    @endif

                                    <div class="text-muted small">
                                        {{ ucfirst($list->status ?? '-') }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge {{ $typeBadgeClass }} learning-record-badge">
                                        {{ $type }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $classification = $content->classification->classification_name ?? '-';

                                        $colors = [
                                            'SHE'                     => 'bg-red-lt text-red',
                                            'Technology'              => 'bg-blue-lt text-blue',
                                            'Basic & Common Duties'   => 'bg-green-lt text-green',
                                            'Production'              => 'bg-indigo-lt text-indigo',
                                            'Commercial'              => 'bg-orange-lt text-orange',
                                            'Human Resources'         => 'bg-pink-lt text-pink',
                                            'Leadership'              => 'bg-purple-lt text-purple',
                                            'Quality'                 => 'bg-cyan-lt text-cyan',
                                            'IT'                      => 'bg-azure-lt text-azure',
                                            'Security'                => 'bg-dark-lt text-dark',
                                        ];

                                        $badgeClass = $colors[$classification] ?? 'bg-secondary-lt text-secondary';
                                    @endphp

                                    <span class="badge {{ $badgeClass }}">
                                        {{ $classification }}
                                    </span>
                                </td>

                                <td class="small">{{ $content->duration ?? '-' }}</td>

                                <td>
                                    <div class="learning-record-progress">
                                        <div class="percent">{{ $progress }}%</div>

                                        @if(!is_null($minimumProgress) && (int) $minimumProgress > 0)
                                            <div class="minimum {{ $isProgressPassed ? '' : 'is-failed' }}">
                                                Min {{ $minimumProgress }}%
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="small">
                                    {{ $list->updated_at ? date('Y-m-d H:i', strtotime($list->updated_at)) : '-' }}
                                </td>

                                <td>
                                    @if(!is_null($score))
                                        <span class="badge {{ $isScorePassed ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }} learning-record-badge">
                                            {{ $score }}
                                        </span>

                                        @if(!is_null($minimumScore) && (int) $minimumScore > 0)
                                            <div class="small text-muted mt-1">
                                                Min {{ $minimumScore }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                            -
                                        </span>

                                        @if(!is_null($minimumScore) && (int) $minimumScore > 0)
                                            <div class="small text-danger mt-1">
                                                Butuh score min {{ $minimumScore }}
                                            </div>
                                        @endif
                                    @endif
                                        <td>
                                            @if($canGenerateCertificate)
                                                <a href="{{ route('certificate.generate', $list->id) }}"
                                                class="btn btn-sm btn-primary learning-record-cert-btn">
                                                    <i class="ti ti-certificate me-1"></i>
                                                    Certificate
                                                </a>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                                    <i class="ti ti-certificate-off me-1"></i>
                                                    Not Available
                                                </span>
                                            @endif
                                        </td>

                            </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
