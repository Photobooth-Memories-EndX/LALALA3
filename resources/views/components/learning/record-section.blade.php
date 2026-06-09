@props([
    'contentUser',
    'averageProgress' => 0,
    'certificateSettings' => collect(),
])

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
        <h3 class="mb-1">Learning Record</h3>
        <div class="text-muted">Riwayat pembelajaran yang sudah selesai.</div>

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
        <h3 class="card-title mb-1">Daftar Riwayat Training</h3>
        <div class="text-muted small">Training yang sudah Anda selesaikan.</div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table mb-0" id="recordTable">
                <thead>
                    <tr>
                        <th class="text-muted">No</th>
                        <th class="text-muted">Nama Kelas</th>
                        <th class="text-muted">Type</th>
                        <th class="text-muted">Classification</th>
                        <th class="text-muted">Duration</th>
                        <th class="text-muted">Progress</th>
                        <th class="text-muted">Finish Date</th>
                        <th class="text-muted">Score Test</th>
                        <th class="text-muted">Certificate</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($contentUser as $list)
                        @if (($list->finish ?? '0') == '1')
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

                                $minimumProgress = $certificateSetting->minimum_progress ?? 100;
                                $minimumScore = $certificateSetting->minimum_score ?? null;

                                $isProgressPassed = $progress >= $minimumProgress;

                                $isAssessmentPassed = true;

                                if ($certificateSetting && $certificateSetting->is_assessment_required) {
                                    $isAssessmentPassed = !is_null($score)
                                        && !is_null($minimumScore)
                                        && $score >= $minimumScore;
                                }

                                $canGenerateCertificate = $certificateSetting
                                    && $certificateSetting->is_active
                                    && $isProgressPassed
                                    && $isAssessmentPassed;
                            @endphp

                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>

                                <td style="min-width: 280px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm {{ in_array($type, ['Video', 'YouTube']) ? 'bg-red-lt text-red' : 'bg-yellow-lt text-yellow' }}">
                                            @if (in_array($type, ['Video', 'YouTube']))
                                                <i class="ti ti-video"></i>
                                            @else
                                                <i class="ti ti-file-description"></i>
                                            @endif
                                        </span>

                                        <div>
                                            @if ($content && !empty($content->slug))
                                                <a href="{{ route('content.user.view', $content->slug) }}" class="fw-semibold text-reset line-clamp-2">
                                                    {{ $content->title ?? '-' }}
                                                </a>
                                            @else
                                                <div class="fw-semibold line-clamp-2">{{ $content->title ?? '-' }}</div>
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

                                <td style="min-width: 150px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $progress }}%</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                                    </div>
                                </td>

                                <td style="min-width: 150px;">
                                    {{ $list->updated_at ? date('Y-m-d H:i', strtotime($list->updated_at)) : '-' }}
                                </td>

                                <td>
                                    {{ $score ?? '-' }}
                                </td>

                                <td>
                                    @if($canGenerateCertificate)
                                        <a href="{{ route('certificate.generate', $list->id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="ti ti-certificate mr-1"></i>
                                            Generate
                                        </a>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">
                                            Not Available
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>