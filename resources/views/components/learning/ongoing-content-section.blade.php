@props([
    // Paginator card Content (Video/Document) — dari LearningOngoingController::content().
    'ongoingContents',
])

<section class="learning-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">

        <x-learning.ongoing-content-filter />

        @if ($ongoingContents->count() > 0)
            <div class="row g-4 mb-4" id="learningGrid">
                @foreach ($ongoingContents as $card)
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
                            $type = strtolower($content->type ?? 'content');
                            $program = strtolower($list->status ?? 'optional');

                            $year = $list->created_at
                                ? \Carbon\Carbon::parse($list->created_at)->format('Y')
                                : (
                                    $list->updated_at
                                        ? \Carbon\Carbon::parse($list->updated_at)->format('Y')
                                        : 'unknown'
                                );

                            // Badge bulan: prioritas content_users.created_at, lalu contents.created_at.
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
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($ongoingContents->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $ongoingContents->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <div class="empty-title">No learning is currently underway</div>
                    <div class="empty-subtitle text-muted">
                        Data for the rotating class will appear here.
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>
