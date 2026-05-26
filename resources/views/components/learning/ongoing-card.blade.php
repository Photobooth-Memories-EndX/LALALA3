@props([
    'title',
    'slug',
    'classification',
    'duration',
    'type',
    'progress',
    'program',
    'year',
    'active',
    'statusLabel',
    'thumbnail',
])

@php
    $title = $title ?? '-';
    $slug = $slug ?? null;
    $classification = $classification ?? '-';
    $duration = $duration ?? '-';
    $type = $type ?? 'Content';
    $progress = (int) ($progress ?? 0);
    $program = strtolower($program ?? 'optional');
    $year = $year ?? 'unknown';
    $active = $active ?? '-';
    $statusLabel = $statusLabel ?? 'ongoing';
    $thumbnail = $thumbnail ?? null;

    $typeLabel = $type;
    $typeLower = strtolower($typeLabel);

    if (str_contains($typeLower, 'video') || str_contains($typeLower, 'youtube')) {
        $placeholderIcon = 'ti ti-video';
    } elseif (str_contains($typeLower, 'pdf') || str_contains($typeLower, 'doc')) {
        $placeholderIcon = 'ti ti-file-description';
    } else {
        $placeholderIcon = 'ti ti-book';
    }

    $slugUrl = !blank($slug) ? route('content.user.view', $slug) : null;
@endphp

<div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 learning-modern-card">
    <div class="position-relative">
        @if ($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $title }}" class="w-100 learning-thumb" loading="lazy" decoding="async">
        @else
            <div class="learning-placeholder">
                <div class="text-center text-primary">
                    <div class="avatar avatar-xl bg-white text-primary shadow-sm mb-2 mx-auto">
                        <i class="{{ $placeholderIcon }} fs-1"></i>
                    </div>
                    <div class="fw-semibold small text-uppercase">{{ $typeLabel }}</div>
                </div>
            </div>
        @endif

        <div class="position-absolute top-0 start-0 p-3 d-flex flex-column gap-2">
            <span class="badge bg-blue text-white shadow-sm">{{ $typeLabel }}</span>
            <span class="badge bg-azure-lt text-azure border border-1 border-white">Sedang Berlangsung</span>
        </div>

        <div class="position-absolute top-0 end-0 p-3">
            <span class="badge bg-dark text-white bg-opacity-50 shadow-sm">{{ $duration }}</span>
        </div>
    </div>

    <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
            <div class="flex-fill">
                <h3 class="card-title mb-2 line-clamp-2 learning-card-title">{{ $title }}</h3>
                <div class="text-muted small line-clamp-2">{{ $classification }}</div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge bg-primary-lt text-primary">{{ ucfirst($program) }}</span>
            <span class="badge bg-gray-100 text-secondary">{{ $year }}</span>
            <span class="badge bg-green-lt text-green">{{ $statusLabel }}</span>
        </div>

        <div class="mb-2">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Progress</span>
                <span class="fw-semibold">{{ $progress }}%</span>
            </div>
            <div class="progress progress-sm learning-progress-bar">
                <div class="progress-bar bg-primary" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <div class="small text-muted mb-3">
            Aktiv: {{ $active }}
        </div>

        @if ($slugUrl)
            <a href="{{ $slugUrl }}" class="btn btn-primary w-100 mt-auto">
                Lanjutkan Learning
            </a>
        @else
            <button type="button" class="btn btn-primary w-100 mt-auto" disabled>
                Lanjutkan Learning
            </button>
        @endif
    </div>
</div>