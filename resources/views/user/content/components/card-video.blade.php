@php
    $thumbnailPath = public_path('storage/files/content/' . $item->id . '/thumb.jpg');

    if ($item->type === 'YouTube') {
        $thumbnail = 'https://img.youtube.com/vi/' . $item->link . '/mqdefault.jpg';
    } elseif (file_exists($thumbnailPath)) {
        $thumbnail = '/storage/files/content/' . $item->id . '/thumb.jpg';
    } else {
        $thumbnail = '/default-video.jpg';
    }

    $today = \Carbon\Carbon::today();
    $publishedDate = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
    $updatedDate = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at) : null;
    $hasDeadline = (bool) $item->has_deadline;
    $deadlineDate = $hasDeadline && filled($item->to_date)
        ? \Carbon\Carbon::parse($item->to_date)->startOfDay()
        : null;
    $isExpired = false;
    $deadlineStateClass = '';
    $deadlineRemaining = null;

    if ($deadlineDate) {
        if ($deadlineDate->isSameDay($today)) {
            $deadlineStateClass = 'is-today';
            $deadlineRemaining = 'Due Today';
        } elseif ($deadlineDate->lt($today)) {
            $deadlineStateClass = 'is-expired';
            $deadlineRemaining = 'Expired';
            $isExpired = true;
        } else {
            $days = $today->diffInDays($deadlineDate);
            $deadlineStateClass = 'is-upcoming';
            $deadlineRemaining = $days . ' ' . ($days === 1 ? 'Day' : 'Days') . ' Due';
        }
    }

    $learningHoursVal = $item->learning_hours ?? null;
    if ($learningHoursVal === null || $learningHoursVal === '') {
        $durationSec = \App\Support\DurationFormatter::toSeconds($item->duration);
        $learningHoursVal = $durationSec > 0 ? round($durationSec / 3600, 1) : 0.5;
    }
    $learningHoursNum = (float) $learningHoursVal;
    $learningHoursDisplay = ($learningHoursNum == (int) $learningHoursNum) ? (int) $learningHoursNum : $learningHoursNum;
    $learningHoursText = $learningHoursDisplay . ' ' . ($learningHoursNum == 1 ? 'Hour' : 'Hours');

    $authorName = $item->author ?? 'Training Team';
@endphp

<div class="card content-card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
    <div class="position-relative">
        @if ($isExpired)
            <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
        @else
            <a href="{{ route('content.user.view', $item->slug) }}" class="d-block text-decoration-none text-reset">
                <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
            </a>
        @endif

        <span class="badge bg-yellow text-white position-absolute top-0 end-0 m-3" style="pointer-events: none;">
            {{ \App\Support\ContentTypeHelper::label($item->type) }}
        </span>
    </div>

    <div class="card-body p-3 d-flex flex-column justify-content-between">
        <div>
            <div class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2">
                <span>
                    <i class="ti ti-eye me-1"></i>{{ number_format($item->views ?? 0) }} Views
                </span>
                <span class="text-muted">&bull;</span>
                <span>
                    <i class="ti ti-clock me-1"></i>{{ \App\Support\DurationFormatter::format($item->duration, 'Self-paced') }}
                </span>
            </div>

            <h3 class="card-title content-card-title mb-1 fw-bold">
                @if ($isExpired)
                    <span title="{{ $item->title }}">{{ $item->title }}</span>
                @else
                    <a href="{{ route('content.user.view', $item->slug) }}"
                       class="text-reset text-decoration-none"
                       title="{{ $item->title }}">
                        {{ $item->title }}
                    </a>
                @endif
            </h3>

            <div class="small text-muted mb-1 d-flex align-items-center">
                <i class="ti ti-users me-1"></i>
                <span>Author : {{ $authorName }}</span>
            </div>

            <div class="small text-muted mb-1">
                <i class="ti ti-hourglass me-1"></i>Learning Hours: {{ $learningHoursText }}
            </div>

            <div class="small text-muted mb-1">
                <i class="ti ti-calendar me-1"></i>Published on {{ $publishedDate ? $publishedDate->format('d M Y') : '-' }}
            </div>

            <div class="small text-muted mb-1.5">
                <i class="ti ti-calendar-due me-1"></i>Period: {{ $deadlineDate ? $deadlineDate->format('d M Y') : 'No period' }}
            </div>

            @if ($deadlineRemaining)
                <div class="mb-1.5">
                    @if($isExpired)
                        <span class="badge bg-danger-lt text-danger">
                            <i class="ti ti-clock-x me-1"></i>
                            Expired
                        </span>
                    @elseif($deadlineStateClass === 'is-today')
                        <span class="badge bg-warning-lt text-warning">
                            <i class="ti ti-calendar-due me-1"></i>
                            {{ $deadlineRemaining }}
                        </span>
                    @else
                        <span class="badge bg-info-lt text-info">
                            <i class="ti ti-calendar-due me-1"></i>
                            {{ $deadlineRemaining }}
                        </span>
                    @endif
                </div>
            @endif

            @if (!empty($item->description))
                <div class="small text-muted mb-2">
                    {{ Str::limit(strip_tags($item->description), 60) }}
                </div>
            @endif
        </div>

        <div class="mt-2 pt-0">
            @if ($isExpired)
                <button type="button" class="btn btn-danger-lt w-100 rounded-pill" disabled>
                    <i class="ti ti-lock me-1"></i>
                    Akses Expired
                </button>
            @else
                <a href="{{ route('content.user.view', $item->slug) }}"
                   class="btn btn-primary w-100 rounded-pill">
                    <i class="ti ti-player-play me-1"></i>
                    Start Learning
                </a>
            @endif
        </div>
    </div>
</div>
