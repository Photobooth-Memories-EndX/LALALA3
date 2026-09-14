@php
    $thumbnailPath = public_path('storage/files/content/' . $item->id . '/thumb.jpg');
    $thumbnail = file_exists($thumbnailPath)
        ? '/storage/files/content/' . $item->id . '/thumb.jpg'
        : null;

    $today = \Carbon\Carbon::today();
    $publishedDate = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
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
    $learningHoursDisplay = number_format($learningHoursNum, 2);
    $learningHoursText = $learningHoursDisplay . ' Hours';

    $authorName = $item->author ?? 'Training Team';
    $cardUrl = route('content.user.view', $item->slug);
@endphp

<div class="card content-card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
    <div class="position-relative">
        @if ($isExpired)
            @if ($thumbnail)
                <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
            @else
                <div class="content-card-thumb d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="text-center text-white p-3">
                        <i class="ti ti-world" style="font-size: 2.5rem; opacity: 0.9;"></i>
                        <div class="small fw-semibold mt-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">External Course</div>
                    </div>
                </div>
            @endif
        @else
            <a href="{{ $cardUrl }}" class="d-block text-decoration-none text-reset">
                @if ($thumbnail)
                    <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
                @else
                    <div class="content-card-thumb d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                        <div class="text-center text-white p-3">
                            <i class="ti ti-world" style="font-size: 2.5rem; opacity: 0.9;"></i>
                            <div class="small fw-semibold mt-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 0.75rem;">External Course</div>
                        </div>
                    </div>
                @endif
            </a>
        @endif

        <span class="badge bg-indigo text-white position-absolute top-0 end-0 m-3 d-inline-flex align-items-center gap-1 shadow-sm" style="pointer-events: none;">
            <i class="ti ti-world" style="font-size: 0.85rem;"></i>
            <span>MOOC</span>
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
                    <a href="{{ $cardUrl }}"
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
        </div>

        <div class="mt-2 pt-0">
            @if ($isExpired)
                <button type="button" class="btn btn-danger-lt w-100 rounded-pill" disabled>
                    <i class="ti ti-lock me-1"></i>
                    Akses Expired
                </button>
            @else
                <a href="{{ $cardUrl }}"
                   class="btn btn-primary btn-learning-primary w-100 rounded-pill d-inline-flex align-items-center justify-content-center gap-1">
                    <i class="ti ti-world me-1"></i>
                    View Course
                </a>
            @endif
        </div>
    </div>
</div>
