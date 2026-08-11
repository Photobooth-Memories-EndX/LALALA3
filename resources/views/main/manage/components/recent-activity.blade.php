<div class="card border-0 shadow-sm h-100">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1">Recent Activity</h3>
                <div class="text-muted small">
                    Latest updates from Content, Assessments, Playlists, and News.
                </div>
            </div>

            <span class="badge bg-primary-lt">
                Latest
            </span>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($recentActivities as $activity)
                <a href="{{ $activity['url'] }}"
                   class="list-group-item list-group-item-action px-0">

                    <div class="d-flex align-items-center">
                        <span class="avatar bg-{{ $activity['color'] }}-lt text-{{ $activity['color'] }} me-3">
                            <i class="{{ $activity['icon'] }}"></i>
                        </span>

                        <div>
                            <div class="fw-semibold">
                                {{ $activity['type'] }} Added
                            </div>

                            <div class="text-muted small">
                                {{ \Illuminate\Support\Str::limit($activity['title'], 55) }}
                            </div>
                        </div>

                        <div class="ms-auto text-muted small">
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
