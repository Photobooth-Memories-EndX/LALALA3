<div class="card border-0 shadow-sm h-100 admin-activity-card">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1">Recent Activity</h3>
                <div class="text-muted small">
                    Latest updates from Content, Assessments, Playlists, and News.
                </div>
            </div>

            <span class="badge bg-primary-lt px-2.5 py-1">
                Latest
            </span>
        </div>

        <div class="list-group list-group-flush admin-activity-list">
            @forelse ($recentActivities as $activity)
                <a href="{{ $activity['url'] }}"
                   class="list-group-item list-group-item-action px-0 py-3 border-bottom">

                    <div class="d-flex align-items-center">
                        <span class="avatar bg-{{ $activity['color'] }}-lt text-{{ $activity['color'] }} me-3 activity-avatar">
                            <i class="{{ $activity['icon'] }}"></i>
                        </span>

                        <div class="min-w-0 me-3">
                            <div class="activity-title">
                                {{ $activity['type'] }} Added
                            </div>

                            <div class="activity-desc text-truncate">
                                {{ \Illuminate\Support\Str::limit($activity['title'], 55) }}
                            </div>
                        </div>

                        <div class="ms-auto activity-time">
                            {{ $activity['time']->diffForHumans() }}
                        </div>
                    </div>

                </a>
            @empty
                <div class="text-center text-muted py-4">
                    Belum ada aktivitas terbaru.
                </div>
            @endforelse
        </div>

    </div>
</div>
