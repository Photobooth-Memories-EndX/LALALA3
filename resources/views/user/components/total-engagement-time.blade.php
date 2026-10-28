@php
    $initialSeconds = (int) ($initialDuration ?? 0);
    $initialFormatted = sprintf(
        '%02d:%02d:%02d',
        floor($initialSeconds / 3600),
        floor(($initialSeconds % 3600) / 60),
        $initialSeconds % 60
    );
@endphp

<div class="card border-0 shadow-sm rounded-4 mt-3 total-engagement-card"
     id="totalEngagementCard"
     data-target-type="{{ $targetType }}"
     data-content-id="{{ $targetId }}"
     @if(isset($trainingPlanId)) data-trainingplan-id="{{ $trainingPlanId }}" @endif
     data-initial-seconds="{{ $initialSeconds }}"
     data-heartbeat-url="{{ route('engagement-time.heartbeat') }}"
     data-csrf-token="{{ csrf_token() }}"
     aria-live="polite">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="engagement-icon-wrapper d-flex align-items-center justify-content-center rounded-3">
                    <i class="fas fa-stopwatch" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="engagement-label text-muted small fw-semibold text-uppercase">
                        Total Engagement Time
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                        <span class="engagement-duration fs-2 fw-bold text-dark font-monospace" id="engagementDurationDisplay">
                            {{ $initialFormatted }}
                        </span>
                        <span class="badge engagement-badge is-paused" id="engagementStatusBadge">
                            <span class="engagement-dot" aria-hidden="true"></span>
                            <span id="engagementStatusText">Paused</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="engagement-hint text-muted small d-none d-sm-block text-end">
                <span class="d-block fw-medium engagement-hint-title">Active Time Tracking</span>
                <span class="engagement-hint-desc text-muted">Pauses automatically when idle &gt; 60s</span>
            </div>
        </div>
    </div>
</div>
