@php
    $contentType = strtolower((string) ($item->type ?? 'Document'));
    $typeBadgeClass = match (true) {
        in_array($contentType, ['document', 'pdf', 'sop/regulation'], true) => 'bg-cyan',
        $contentType === 'playlist'                                        => 'bg-green',
        $contentType === 'content'                                         => 'bg-red',
        default                                                            => 'bg-cyan',
    };

    $thumbnailPath = public_path('storage/files/content/' . $item->id . '/thumb.jpg');
    $thumbnail = file_exists($thumbnailPath)
        ? '/storage/files/content/' . $item->id . '/thumb.jpg'
        : '/pdf.png';

    $today = \Carbon\Carbon::today();
    $publishedDate = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
    $updatedDate = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at) : null;
    $deadlineDate = $item->date ? \Carbon\Carbon::parse($item->date)->startOfDay() : null;
    $showUpdatedDate = $updatedDate && (!$publishedDate || !$updatedDate->equalTo($publishedDate));
    $deadlineStateClass = '';
    $deadlineRemaining = null;

    if ($deadlineDate) {
        if ($deadlineDate->isSameDay($today)) {
            $deadlineStateClass = 'is-today';
            $deadlineRemaining = 'Today';
        } elseif ($deadlineDate->lt($today)) {
            $days = $deadlineDate->diffInDays($today);
            $deadlineStateClass = 'is-expired';
            $deadlineRemaining = 'Expired ' . $days . ' ' . ($days === 1 ? 'Day' : 'Days') . ' Ago';
        } else {
            $days = $today->diffInDays($deadlineDate);
            $deadlineStateClass = 'is-upcoming';
            $deadlineRemaining = $days . ' ' . ($days === 1 ? 'Day' : 'Days') . ' Left';
        }
    }
@endphp

<div class="card content-card">
    <a href="{{ route('content.user.view', $item->slug) }}">
        <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
    </a>

    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <span class="badge {{ $typeBadgeClass }}">{{ $item->type ?? 'Document' }}</span>
            <span class="ms-auto text-muted small">
                <i class="ti ti-file-text"></i>
                Document
            </span>
        </div>

        <h3 class="card-title content-card-title mb-2">
            <a href="{{ route('content.user.view', $item->slug) }}"
               class="text-decoration-none"
               title="{{ $item->title }}">
                {{ $item->title }}
            </a>
        </h3>

        <div class="content-card-details">
            <div class="content-card-meta text-muted mb-2">
                <i class="ti ti-folder me-1"></i>
                {{ $item->classification->classification_name ?? '-' }}
            </div>

            <div class="d-flex align-items-center content-card-meta text-muted mb-2">
                <span class="text-truncate me-2">
                    <i class="ti ti-user me-1"></i>
                    {{ $item->author ?? 'Training Team' }}
                </span>
                <span class="ms-auto flex-shrink-0">
                    <i class="ti ti-eye me-1"></i>
                    {{ $item->views ?? 0 }}
                </span>
            </div>

            <div class="content-card-dates mb-3">
                <div class="content-card-date-row">
                    <i class="ti ti-calendar-plus"></i>
                    <span>Published: {{ $publishedDate ? $publishedDate->format('d M Y') : '-' }}</span>
                </div>
                {{-- <div class="content-card-date-row">
                    <i class="ti ti-refresh"></i>
                    <span>Last Updated: {{ $showUpdatedDate ? $updatedDate->format('d M Y') : '-' }}</span>
                </div> --}}
                <div class="content-card-date-row">
                    <i class="ti ti-calendar-due"></i>
                    <span>Deadline: {{ $deadlineDate ? $deadlineDate->format('d M Y') : 'No deadline' }}</span>
                </div>
                {{-- <div class="content-card-date-row">
                    <i class="ti ti-hourglass"></i>
                    <span class="content-card-deadline-state {{ $deadlineStateClass }}">
                        {{ $deadlineRemaining ?? 'No deadline' }}
                    </span>
                </div> --}}
            </div>
        </div>

        <a href="{{ route('content.user.view', $item->slug) }}"
           class="btn btn-primary w-100 content-card-action">
            <i class="ti ti-player-play me-1"></i>
            Read Document
        </a>
    </div>
</div>
