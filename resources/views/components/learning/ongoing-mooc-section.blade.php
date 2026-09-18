@props([
    'ongoingMoocs',
])

<section class="learning-section-wrapper mooc-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">
        <div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
            <div class="card-body p-4">
                <div class="ongoing-filter-heading">
                    <div class="ongoing-filter-heading-copy">
                        <h3 class="card-title mb-1">MOOC</h3>
                        <div class="text-muted small">Massive Open Online Courses</div>
                    </div>
                    <x-learning.ongoing-mode-switch mode="mooc" />
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
