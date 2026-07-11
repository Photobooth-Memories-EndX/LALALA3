@props([
    // Paginator card Playlist — dari LearningOngoingController::playlist().
    'ongoingPlaylists',
])

<section class="learning-section-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">

        <x-learning.ongoing-playlist-filter />

        @if ($ongoingPlaylists->count() > 0)
            <div class="row g-4 mb-4" id="learningGrid">
                @foreach ($ongoingPlaylists as $card)
                    @php
                        $plan = $card['plan'];

                        $plTotal    = (int) ($plan->total_items ?? 0);
                        $plFinished = (int) ($plan->finished_items ?? 0);
                        $plPercent  = (int) ($plan->progress_percent ?? 0);

                        // Entry point playlist: /playlist/{playlistSlug}/{firstContentSlug}.
                        $continueUrl = (!blank($plan->slug) && !blank($plan->first_content_slug ?? null))
                            ? route('playlist.user.view', [$plan->slug, $plan->first_content_slug])
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
                @endforeach
            </div>

            @if ($ongoingPlaylists->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $ongoingPlaylists->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <div class="empty-title">No playlist is currently underway</div>
                    <div class="empty-subtitle text-muted">
                        Playlists you are working on will appear here.
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>
