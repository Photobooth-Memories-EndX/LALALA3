@props([
    'ongoingContentUser',
    'ongoingLearning' => 0,
    'averageProgress' => 0,
    'ongoingPlaylists' => null,
])

@php
    $ongoingPlaylists = $ongoingPlaylists ?? collect();
@endphp

<section class="learning-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">

        {{-- Hero "Continue Your Learning" kini dirender sekali di dashboard.blade.php
             (x-learning.hero-section) sehingga tampil konsisten di semua tab. --}}
        <x-learning.ongoing-filter :playlists="$ongoingPlaylists" />

        @if ($ongoingContentUser->count() > 0)
            <div class="row g-4 mb-4" id="learningGrid">
                @foreach ($ongoingContentUser as $card)
                    {{-- ── Card Playlist (Training Plan) ────────────────────────────────── --}}
                    @if (($card['card_type'] ?? 'content') === 'playlist')
                        @php
                            $plan = $card['plan'];

                            $plTotal    = (int) ($plan->total_items ?? 0);
                            $plFinished = (int) ($plan->finished_items ?? 0);
                            $plPercent  = (int) ($plan->progress_percent ?? 0);
                            $continueUrl = !blank($plan->next_slug ?? null)
                                ? route('content.user.view', $plan->next_slug)
                                : null;

                            // Thumbnail playlist; null = card memakai placeholder default Playlist.
                            $plThumbnail = $plan->thumbnail_url ?? null;
                        @endphp

                        <div class="col-md-6 col-xl-4">
                            <x-learning.ongoing-card
                                card-type="playlist"
                                :title="$plan->title ?? '-'"
                                :progress="$plPercent"
                                :module-count="$plTotal"
                                :completed-count="$plFinished"
                                :continue-url="$continueUrl"
                                :thumbnail="$plThumbnail"
                            />
                        </div>

                    {{-- ── Card Content (Video / Document) ──────────────────────────────── --}}
                    @else
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
                                $duration = $content->duration ?? '-';
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

                                // Badge Playlist asal — relasi sudah di-eager-load di controller (bebas N+1).
                                $playlistNames = $content->relationLoaded('trainingPlans')
                                    ? $content->trainingPlans->pluck('title')->filter()->values()->all()
                                    : [];
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
                                    :active="$list->active ?? '-'"
                                    :status-label="$statusLabel"
                                    :thumbnail="$thumbnail"
                                    :playlist-names="$playlistNames"
                                />
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>

            @if ($ongoingContentUser->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $ongoingContentUser->links('pagination::bootstrap-5') }}
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
