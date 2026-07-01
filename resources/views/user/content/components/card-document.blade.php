@php
    $thumbnailPath = public_path('storage/files/content/' . $item->id . '/thumb.jpg');

    if (file_exists($thumbnailPath)) {
        $thumbnail = '/storage/files/content/' . $item->id . '/thumb.jpg';
    } else {
        $thumbnail = '/pdf.png';
    }
@endphp

<div class="card content-card">
    <a href="{{ route('content.user.view', $item->slug) }}">
        <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
    </a>

    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-green-lt">
                {{ $item->type ?? 'Document' }}
            </span>

            <span class="ms-auto text-muted small">
                <i class="ti ti-file-text"></i>
                Document
            </span>
        </div>

        <h3 class="card-title content-card-title mb-2">
            <a href="{{ route('content.user.view', $item->slug) }}" class="text-decoration-none">
                {{ \Illuminate\Support\Str::limit($item->title, 55) }}
            </a>
        </h3>

        <div class="content-card-meta text-muted mb-2">
            <i class="ti ti-folder me-1"></i>
            {{ $item->classification->classification_name ?? '-' }}
        </div>

        <div class="d-flex align-items-center content-card-meta text-muted mb-2">
            <span>
                <i class="ti ti-user me-1"></i>
                {{ $item->author ?? 'Training Team' }}
            </span>

            <span class="ms-auto">
                <i class="ti ti-eye me-1"></i>
                {{ $item->views ?? 0 }}
            </span>
        </div>

        <div class="content-card-deadline mb-3">
            @if (!empty($item->has_deadline) && $item->from_date && $item->to_date)
                <i class="ti ti-calendar-due"></i>
                <span>
                    Deadline:
                    {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                    –
                    {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                </span>
            @endif
        </div>

        <a href="{{ route('content.user.view', $item->slug) }}"
           class="btn btn-success w-100 content-card-action">
            <i class="ti ti-file-text me-1"></i>
            Read Document
        </a>
    </div>
</div>