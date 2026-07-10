@props([
    // Collection of ['setting' => CertificateSetting, 'certificate' => ?Certificate, 'finished_at' => ?Carbon]
    // Sudah terurut: tanggal selesai terbaru lebih dulu.
    'items' => collect(),
])

@if ($items->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="card-body p-4 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title mb-1">
                        <i class="ti ti-certificate text-green me-1"></i>
                        Certificate Ready
                    </h3>
                    <div class="text-muted small">
                        You have met all requirements. Download your certificate below.
                    </div>
                </div>

                <span class="badge bg-green-lt text-green">
                    {{ $items->count() }} Ready
                </span>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-3">
                @foreach ($items as $item)
                    @php
                        $setting     = $item['setting'];
                        $certificate = $item['certificate'];

                        $typeBadge = match ($setting->certificate_type) {
                            'playlist' => 'bg-purple-lt text-purple',
                            'content'  => 'bg-blue-lt text-blue',
                            default    => 'bg-azure-lt text-azure',
                        };

                        // "Training Plan" tidak dipakai di tampilan user — istilahnya Playlist.
                        $typeLabel = $setting->certificate_type === 'playlist'
                            ? 'Playlist'
                            : ucfirst($setting->certificate_type);
                    @endphp

                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 border shadow-sm rounded-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span>

                                    <span class="badge bg-green-lt text-green">
                                        <i class="ti ti-circle-check me-1"></i>Certificate Ready
                                    </span>
                                </div>

                                <div class="fw-semibold line-clamp-2 mb-1" title="{{ $setting->sourceTitle() }}">
                                    {{ $setting->sourceTitle() }}
                                </div>

                                <div class="text-muted small mb-3">
                                    @if (!empty($item['finished_at']))
                                        Completed {{ $item['finished_at']->format('d M Y') }}
                                    @else
                                        All requirements fulfilled
                                    @endif
                                </div>

                                @if ($certificate)
                                    <a href="{{ route('certificate.download', $certificate->credential_id) }}"
                                       class="btn btn-sm btn-success w-100">
                                        <i class="ti ti-download me-1"></i>
                                        Download Certificate
                                    </a>
                                @else
                                    <form method="POST" action="{{ route('certificate.generate.setting', $setting) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                            <i class="ti ti-certificate me-1"></i>
                                            Generate Certificate
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
