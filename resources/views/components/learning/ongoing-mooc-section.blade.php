@props([
    'ongoingMoocs',
])

<section class="learning-section-wrapper mooc-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">
        <div class="card border-0 shadow-sm rounded-4 mb-3 my-task-filter-card ongoing-filter-card">
            <div class="card-body p-3 p-md-4">
                {{-- Header: Title & Subtitle --}}
                <div class="mb-3">
                    <h2 class="fw-bold mb-1 my-task-filter-title">Learning On Going</h2>
                    <p class="text-muted small mb-0 my-task-filter-subtitle">Track and continue your in-progress courses, playlists, and learning modules.</p>
                </div>

                {{-- Category Filter Pills --}}
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
                    <button type="button"
                            class="my-task-category-pill ongoing-mode-btn"
                            data-mode="content"
                            title="Filter by Content">
                        <i class="ti ti-file-text me-1"></i> Content
                    </button>
                    <button type="button"
                            class="my-task-category-pill ongoing-mode-btn"
                            data-mode="playlist"
                            title="Filter by Playlist">
                        <i class="ti ti-list-details me-1"></i> Playlist
                    </button>
                    <button type="button"
                            class="my-task-category-pill ongoing-mode-btn active"
                            data-mode="mooc"
                            title="Filter by MOOC">
                        <i class="ti ti-world me-1"></i> MOOC
                    </button>
                </div>

                {{-- Subtle Divider Line --}}
                <hr class="my-3 my-task-divider">

                {{-- Fast Filter Toolbar --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100 my-task-toolbar">
                    <div class="text-muted small">
                        Showing all enrolled Massive Open Online Courses (MOOC)
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <x-learning.ongoing-export mode="mooc" />
                    </div>
                </div>
            </div>
        </div>

        @if ($ongoingMoocs->count() > 0)
            <div class="row g-4 mb-4" id="learningGrid">
                @foreach ($ongoingMoocs as $card)
                    @php
                        $list = $card['list'];
                    @endphp

                    @if ($list->content != null)
                        @php
                            $content = $list->content;

                            $classification = $content->classification->classification_name ?? '-';
                            $title = $content->title ?? '-';
                            $slug = $content->slug ?? null;
                            $progress = (int) ($list->progress ?? 0);
                            $duration = \App\Support\DurationFormatter::format($content->duration);
                            $type = 'mooc';
                            $program = strtolower($list->status ?? 'optional');

                            $year = $list->created_at
                                ? \Carbon\Carbon::parse($list->created_at)->format('Y')
                                : (
                                    $list->updated_at
                                        ? \Carbon\Carbon::parse($list->updated_at)->format('Y')
                                        : 'unknown'
                                );

                            $monthSource = $list->created_at ?? ($content->created_at ?? null);
                            $month = $monthSource
                                ? \Carbon\Carbon::parse($monthSource)->format('F')
                                : null;

                            $thumbFile = public_path('storage/files/content/' . $content->id . '/thumb.jpg');
                            $thumbnail = file_exists($thumbFile)
                                ? '/storage/files/content/' . $content->id . '/thumb.jpg'
                                : null;

                            $statusLabel = $list->status ?? 'optional';
                        @endphp

                        <div class="col-md-6 col-xl-4">
                            <x-learning.ongoing-card
                                card-type="mooc"
                                :title="$title"
                                :slug="$slug"
                                :classification="$classification"
                                :duration="$duration"
                                :type="$type"
                                :progress="$progress"
                                :program="$program"
                                :month="$month"
                                :year="$year"
                                :published-at="$content->created_at"
                                :has-deadline="$content->has_deadline"
                                :deadline-at="$content->to_date"
                                :active="$list->active ?? '-'"
                                :status-label="$statusLabel"
                                :thumbnail="$thumbnail"
                                :learning-hours="$content->learning_hours ?? null"
                                :current-learning-hours="$list->current_learning_hours ?? ($card['current_learning_hours'] ?? null)"
                                :total-learning-hours="$list->total_learning_hours ?? ($card['total_learning_hours'] ?? null)"
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($ongoingMoocs->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $ongoingMoocs->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <div class="avatar avatar-xl bg-indigo-lt text-indigo rounded-circle mx-auto mb-3">
                        <i class="ti ti-world fs-1"></i>
                    </div>
                    <h3 class="fw-bold mb-2">No courses in progress</h3>
                    <p class="text-muted mx-auto mb-4" style="max-width: 480px;">
                        You don't have any MOOC courses in progress yet. Explore available courses to start learning.
                    </p>
                    <a href="{{ route('content.user.index', ['type' => 'YouTube']) }}" class="btn btn-primary px-4 py-2 rounded-pill">
                        <i class="ti ti-compass me-1"></i> Explore Courses
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
